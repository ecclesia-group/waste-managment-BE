<?php

namespace App\Http\Controllers\Teams;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\DistrictAssembly;
use App\Models\Facility;
use App\Models\Notification;
use App\Models\Provider;
use App\Models\Role;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TeamMemberController extends Controller
{
    public function index(Request $request)
    {
        $owner = $request->user();
        if (! (bool) ($owner->is_main ?? true)) {
            return self::apiResponse(in_error: true, message: 'Action Failed', reason: 'Only main accounts can view team members', status_code: self::API_FAIL, data: []);
        }

        $context = $this->resolveContext($owner);
        $paginator = $context['modelClass']::query()
            ->where('parent_slug', $context['owner_slug'])
            ->where('is_main', false)
            ->latest()
            ->paginate($this->perPage($request));

        return $this->paginatedApiResponseMapped(
            $paginator,
            'Team members retrieved successfully',
            function ($member) {
                $payload = $member->toArray();
                $payload['rbac'] = $member->rbacForFrontend();

                return $payload;
            }
        );
    }

    public function show(Request $request, string $memberSlug)
    {
        $owner = $request->user();
        if (! (bool) ($owner->is_main ?? true)) {
            return self::apiResponse(in_error: true, message: 'Action Failed', reason: 'Only main accounts can view team members', status_code: self::API_FAIL, data: []);
        }

        $context = $this->resolveContext($owner);
        $member = $this->findOwnedMember($context, $memberSlug);
        if (! $member) {
            return self::apiResponse(in_error: true, message: 'Action Failed', reason: 'Team member not found', status_code: self::API_NOT_FOUND, data: []);
        }

        return self::apiResponse(in_error: false, message: 'Action Successful', reason: 'Team member retrieved successfully', status_code: self::API_SUCCESS, data: $member->toArray());
    }

    public function store(Request $request)
    {
        $owner = $request->user();
        if (! (bool) ($owner->is_main ?? true)) {
            return self::apiResponse(in_error: true, message: 'Action Failed', reason: 'Only main accounts can add team members', status_code: self::API_FAIL, data: []);
        }

        $context = $this->resolveContext($owner);
        $validator = Validator::make($request->all(), [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique($context['table'], 'email')],
            'phone_number' => ['required', 'string', 'max:50', Rule::unique($context['table'], 'phone_number')],
            'role_slug' => ['required', 'string', 'exists:roles,role_slug'],
            'status' => ['sometimes', 'string', 'in:active,inactive,suspended'],
        ]);

        if ($validator->fails()) {
            return self::apiResponse(in_error: true, message: 'Action Failed', reason: $validator->errors()->first(), status_code: self::API_FAIL, data: []);
        }

        $data = static::formatPhoneNumbersInData($validator->validated());
        $role = $this->findOwnedRole($context, $data['role_slug']);
        if (! $role) {
            return self::apiResponse(in_error: true, message: 'Action Failed', reason: 'Role not found for this account', status_code: self::API_NOT_FOUND, data: []);
        }

        $plainPassword = Str::random(8);
        $payload = [
            $context['slug_column'] => (string) Str::uuid(),
            'parent_slug' => $context['owner_slug'],
            'is_main' => false,
            'role_slug' => $role->role_slug,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone_number' => $data['phone_number'],
            'password' => $plainPassword,
            'email_verified_at' => now(),
            'status' => $data['status'] ?? 'active',
        ];
        $payload = $this->addActorRequiredDefaults($context, $owner, $payload);

        $member = $context['modelClass']::create($payload);

        $memberPayload = $member->toArray();
        $memberPayload['rbac'] = $member->rbacForFrontend();

        return self::apiResponse(in_error: false, message: 'Action Successful', reason: 'Team member created successfully', status_code: self::API_CREATED, data: $memberPayload);
    }

    public function update(Request $request, string $memberSlug)
    {
        $owner = $request->user();
        if (! (bool) ($owner->is_main ?? true)) {
            return self::apiResponse(in_error: true, message: 'Action Failed', reason: 'Only main accounts can update team members', status_code: self::API_FAIL, data: []);
        }

        $context = $this->resolveContext($owner);
        $member = $this->findOwnedMember($context, $memberSlug);
        if (! $member) {
            return self::apiResponse(in_error: true, message: 'Action Failed', reason: 'Team member not found', status_code: self::API_NOT_FOUND, data: []);
        }

        $validator = Validator::make($request->all(), [
            'first_name' => ['sometimes', 'string', 'max:100'],
            'last_name' => ['sometimes', 'string', 'max:100'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique($context['table'], 'email')->ignore($member->id)],
            'phone_number' => ['sometimes', 'string', 'max:50', Rule::unique($context['table'], 'phone_number')->ignore($member->id)],
            'role_slug' => ['sometimes', 'string', 'exists:roles,role_slug'],
            'status' => ['sometimes', 'string', 'in:active,inactive,suspended'],
        ]);

        if ($validator->fails()) {
            return self::apiResponse(in_error: true, message: 'Action Failed', reason: $validator->errors()->first(), status_code: self::API_FAIL, data: []);
        }

        $data = static::formatPhoneNumbersInData($validator->validated());
        if (array_key_exists('role_slug', $data)) {
            $role = $this->findOwnedRole($context, $data['role_slug']);
            if (! $role) {
                return self::apiResponse(in_error: true, message: 'Action Failed', reason: 'Role not found for this account', status_code: self::API_NOT_FOUND, data: []);
            }
            $data['role_slug'] = $role->role_slug;
        }

        $member->update($data);
        $payload = $member->fresh()->toArray();
        $payload['rbac'] = $member->fresh()->rbacForFrontend();

        return self::apiResponse(in_error: false, message: 'Action Successful', reason: 'Team member updated successfully', status_code: self::API_SUCCESS, data: $payload);
    }

    public function destroy(Request $request, string $memberSlug)
    {
        $owner = $request->user();
        if (! (bool) ($owner->is_main ?? true)) {
            return self::apiResponse(in_error: true, message: 'Action Failed', reason: 'Only main accounts can delete team members', status_code: self::API_FAIL, data: []);
        }

        $context = $this->resolveContext($owner);
        $member = $this->findOwnedMember($context, $memberSlug);
        if (! $member) {
            return self::apiResponse(in_error: true, message: 'Action Failed', reason: 'Team member not found', status_code: self::API_NOT_FOUND, data: []);
        }

        $member->delete();
        return self::apiResponse(in_error: false, message: 'Action Successful', reason: 'Team member deleted successfully', status_code: self::API_SUCCESS, data: []);
    }

    public function updateStatus(Request $request, string $memberSlug)
    {
        $owner = $request->user();
        if (! (bool) ($owner->is_main ?? true)) {
            return self::apiResponse(in_error: true, message: 'Action Failed', reason: 'Only main accounts can update team member status', status_code: self::API_FAIL, data: []);
        }

        $context = $this->resolveContext($owner);
        $member = $this->findOwnedMember($context, $memberSlug);
        if (! $member) {
            return self::apiResponse(in_error: true, message: 'Action Failed', reason: 'Team member not found', status_code: self::API_NOT_FOUND, data: []);
        }

        $validator = Validator::make($request->all(), [
            'status' => ['required', 'string', 'in:active,inactive,suspended'],
            'suspension_reason' => ['nullable', 'string', 'max:500'],
            'corrective_action' => ['nullable', 'string', 'max:500'],
        ]);

        if ($validator->fails()) {
            return self::apiResponse(in_error: true, message: 'Action Failed', reason: $validator->errors()->first(), status_code: self::API_FAIL, data: []);
        }

        $data = $validator->validated();
        $member->status = $data['status'];

        if (($data['status'] ?? 'active') !== 'active') {
            $member->suspension_reason = $data['suspension_reason'] ?? $member->suspension_reason;
            $member->corrective_action = $data['corrective_action'] ?? $member->corrective_action;
            $member->suspended_at = now();

            Notification::create([
                'actor' => $context['actor'],
                'actor_id' => (string) $member->id,
                'actor_slug' => $member->{$context['slug_column']},
                'title' => 'Account suspended',
                'message' => trim(
                    'Your account has been suspended.'
                    . ($member->suspension_reason ? ' Reason: ' . $member->suspension_reason . '.' : '')
                    . ($member->corrective_action ? ' Corrective action: ' . $member->corrective_action . '.' : '')
                ),
                'type' => 'account_suspension',
            ]);
        } else {
            $member->suspension_reason = null;
            $member->corrective_action = null;
            $member->suspended_at = null;

            Notification::create([
                'actor' => $context['actor'],
                'actor_id' => (string) $member->id,
                'actor_slug' => $member->{$context['slug_column']},
                'title' => 'Account reactivated',
                'message' => 'Your account is active again.',
                'type' => 'account_reactivation',
            ]);
        }

        $member->save();
        $payload = $member->fresh()->toArray();
        $payload['rbac'] = $member->fresh()->rbacForFrontend();

        return self::apiResponse(in_error: false, message: 'Action Successful', reason: 'Team member status updated successfully', status_code: self::API_SUCCESS, data: $payload);
    }

    private function resolveContext($owner): array
    {
        return match (true) {
            $owner instanceof Provider => [
                'actor' => 'provider',
                'modelClass' => Provider::class,
                'table' => 'providers',
                'slug_column' => 'provider_slug',
                'owner_slug' => $owner->provider_slug,
                'login_url' => 'https://wasteprovider.tripsecuregh.com/login',
            ],
            $owner instanceof Facility => [
                'actor' => 'facility',
                'modelClass' => Facility::class,
                'table' => 'facilities',
                'slug_column' => 'facility_slug',
                'owner_slug' => $owner->facility_slug,
                'login_url' => 'https://wastefacility.tripsecuregh.com/login',
            ],
            $owner instanceof DistrictAssembly => [
                'actor' => 'district_assembly',
                'modelClass' => DistrictAssembly::class,
                'table' => 'district_assemblies',
                'slug_column' => 'district_assembly_slug',
                'owner_slug' => $owner->district_assembly_slug,
                'login_url' => 'https://wastemmda.tripsecuregh.com/login',
            ],
            default => [
                'actor' => 'admin',
                'modelClass' => Admin::class,
                'table' => 'admins',
                'slug_column' => 'admin_slug',
                'owner_slug' => $owner->admin_slug,
                'login_url' => 'https://wasteadmin.tripsecuregh.com/login',
            ],
        };
    }

    private function findOwnedRole(array $context, string $roleSlug): ?Role
    {
        return Role::query()
            ->where('role_slug', $roleSlug)
            ->where('actor', $context['actor'])
            ->where('actor_slug', $context['owner_slug'])
            ->first();
    }

    private function findOwnedMember(array $context, string $memberSlug): ?Model
    {
        return $context['modelClass']::query()
            ->where($context['slug_column'], $memberSlug)
            ->where('parent_slug', $context['owner_slug'])
            ->where('is_main', false)
            ->first();
    }

    private function addActorRequiredDefaults(array $context, object $owner, array $payload): array
    {
        if ($context['actor'] === 'facility') {
            $payload['region'] = $owner->region ?: 'Unknown';
        }

        if ($context['actor'] === 'district_assembly') {
            $payload['region'] = $owner->region ?: 'Unknown';
            $payload['district'] = $owner->district ?: 'Unknown';
        }

        return $payload;
    }
}
