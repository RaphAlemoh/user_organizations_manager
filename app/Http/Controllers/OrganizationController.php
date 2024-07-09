<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddUserOrganizationRequest;
use App\Models\Organization;
use App\Http\Requests\StoreOrganizationRequest;
use App\Http\Requests\UpdateOrganizationRequest;
use App\Models\User;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class OrganizationController extends Controller
{
    use ApiResponser;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();
        $organizations = $user->organizations()->get(['orgId', 'name', 'description']);

        return $this->jsonReponse([
            'status' => 'success',
            'message' => 'Organizations retrieved successfully',
            'data' => [
                'organizations' => $organizations->map(function ($org) {
                    return [
                        'orgId' => $org->orgId,
                        'name' => $org->name,
                        'description' => $org->description,
                    ];
                })
            ]
        ],  Response::HTTP_OK);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOrganizationRequest $request)
    {

        $validated = $request->validated();

        $user = Auth::user();

        $organization = Organization::create([
            'orgId' => uniqid(),
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        $user = Auth::user();
        $user->organizations()->attach($organization);

        if ($organization) {
            return $this->jsonReponse([
                'status' => 'success',
                'message' => 'Organization created successfully',
                'data' => [
                    'orgId' => $organization->orgId,
                    'name' => $organization->name,
                    'description' => $organization->description
                ]
            ],  Response::HTTP_CREATED);
        }

        return $this->jsonReponse([
            'status' => 'Bad Request',
            'message' => 'Client error',
            'statusCode' => Response::HTTP_BAD_REQUEST,
        ],  Response::HTTP_BAD_REQUEST);
    }

    /**
     * Display the specified resource.
     */
    public function show($orgId)
    {
        $user = Auth::user();
        $organization = $user->organizations()->where('orgId', $orgId)->first();

        if (!$organization) {
            return $this->jsonReponse([
                'status' => 'error',
                'message' => 'Organization not found',
                'data' => null
            ], Response::HTTP_NOT_FOUND);
        }

        return $this->jsonReponse([
            'status' => 'success',
            'message' => 'Organization retrieved successfully',
            'data' => [
                'orgId' => $organization->orgId,
                'name' => $organization->name,
                'description' => $organization->description
            ]
        ], Response::HTTP_OK);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Organization $organization)
    {
        //
    }


    public function add_user_org(AddUserOrganizationRequest $request, $orgId)
    {
        $validated = $request->validated();

        $organization = Organization::where('orgId', $orgId)->first();

        if (!$organization) {
            return $this->jsonReponse([
                'status' => 'error',
                'message' => 'Organization not found or you do not have permission',
                'data' => null
            ], Response::HTTP_NOT_FOUND);
        }

        $user = User::where('userId', $validated['userId'])->first();
        if ($user) {
            $user->organizations()->attach($organization);
            return $this->jsonReponse([
                'status' => 'success',
                'message' => 'User added to organization successfully',
            ],  Response::HTTP_OK);
        }

        return $this->jsonReponse([
            'status' => 'error',
            'message' => 'User not found',
            'data' => null
        ], Response::HTTP_NOT_FOUND);
    }
}
