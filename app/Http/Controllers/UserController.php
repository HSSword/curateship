<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\User;
use App\Role;
use DB;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $bladeTemplate = $request->ajax() ? 'pages.admin.users.table-with-pagination' : 'pages.admin.users.index';

        $q     = $request->input('q');

        $limit = $request->input('limit') ? $request->input('limit') : 25;
        $sort  = $request->input('sort') ? $request->input('sort') : 'id';
        $order = $request->input('order') ? $request->input('order') : 'desc';

        // work around for status
        $statusOrder = ($order == 'asc') ? 'desc' : 'asc';

        $users = User::
            select('users.*', 'users.permission as status', 'roles.name as role')
            ->leftJoin('roles', 'roles.permission', '=', 'users.permission');

        $users = ($sort == 'status')
                ? $users->orderBy($sort, $statusOrder)
                : $users->orderBy($sort, $order);

        // if search query is not null
        if ($q != null) {
            $users = $users->where('users.name', 'LIKE', '%' . $q . '%')
                ->orWhere ( 'users.username', 'LIKE', '%' . $q . '%' )
                ->orWhere ( 'users.email', 'LIKE', '%' . $q . '%' );
        }

        $users = $users->paginate($limit);

        $availableLimit = ['25', '50', '100', '150', '200'];

        return view($bladeTemplate,
            compact('users', 'q', 'limit', 'availableLimit', 'sort', 'order')
        );
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $user = User::find($id);
        $roles = DB::table('roles')->orderBy('id', 'desc')->get();

        if (!$user) {
            return redirect('/admin/users')->with('responseMessage', 'User not found.');
        }

        return view('pages.admin.users.edit', compact('user', 'roles'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $responseMessage = 'User has been updated.';
        $user = User::find($id);

        if (!$user) {
            return redirect('/admin/users')->with('responseMessage', 'User not found.');
        }

        // validate data
        $this->validate($request,[
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$id],
            'username' => ['required', 'string', 'max:255', 'unique:users,email,'.$id],
        ]);

        // get inputs
        $name = $request->input('name');
        $email = $request->input('email');
        $username = $request->input('username');
        $selectedRoleKey = $request->input('role');

        // save updated user
        $user->name = $name;
        $user->email = $email;
        $user->username = $username;

        // do not update role for currently logged in admin
        if (auth()->user()->id != $id) {
            // get role key
            $selectedRole     = Role::where('key', $selectedRoleKey)->first();
            $permission       = $selectedRole->permission;

            // if user is currently suspended
            if ($user->permission == 0) {
                // then just update the previous permission
                $user->previous_permission = $permission;
            }else{
                // update the current permission
                $user->permission = $permission;
            }
        }

        $saved = $user->save();

        if (!$saved) {
            $responseMessage = 'Failed to save details. Please try again.';
        }

        return back()->with('responseMessage', $responseMessage);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user = User::find($id);
        $responseMessage = 'Something went wrong. Please try again.';

        // if user not found
        if (!$user) {
            return redirect('/admin/users')->with('responseMessage', 'User not found.');
        }

        // if user trying to delete is currently logged in admin
        if ($user->id == auth()->user()->id) {
            $responseMessage = 'You cannot delete your logged in account.';
        }else{
            $email = $user->email;

            $deleted = $user->delete();

            if ($deleted) {
                $responseMessage = 'User account of '. $email . ' has been deleted.';
            }else{
                $responseMessage = 'Failed to delete user '. $email . '. Please try again.';
            }

        }

        return redirect('/admin/users')->with('responseMessage', $responseMessage);
    }

    /**
     * Suspend user account
     * Set `permission` to 0
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function suspend($id)
    {
        $user = User::find($id);
        $responseMessage = 'Something went wrong. Please try again.';

        // if user not found
        if (!$user) {
            return redirect('/admin/users')->with('responseMessage', 'User not found.');
        }

        // if user trying to suspend is currently logged in admin
        if ($user->id == auth()->user()->id) {
            $responseMessage = 'You cannot suspend your logged in account.';
        }else{
            // if user account was already suspended
            if ($user->permission == 0) {
                $responseMessage = 'User '. $user->email. ' was previously suspended.';
            }else{
                $previousPermission = $user->permission;
                $newPermission = 0;

                $user->previous_permission = $previousPermission;
                $user->permission          = $newPermission;
                $saved                     = $user->save();

                if ($saved) {
                    $responseMessage = 'User account for '. $user->email. ' has been suspended.';
                }else{
                    $responseMessage = 'Failed to suspend the account of '. $user->email. '. Please try again.';
                }
            }

        }

        return redirect('/admin/users')->with('responseMessage', $responseMessage);
    }

    /**
     * Activate user account
     * Set `permission` to `previous_permission`
     * Set `previous_permission` to `permission`
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function activate($id)
    {
        $user = User::find($id);
        $responseMessage = 'Something went wrong. Please try again.';

        // if user not found
        if (!$user) {
            return redirect('/admin/users')->with('responseMessage', 'User not found.');
        }

        // if user trying to activate is currently logged in admin
        if ($user->id == auth()->user()->id) {
            $responseMessage = 'You cannot change the status of your account.';
        }else{
            // if user account was already activated
            if ($user->permission > 0) {
                $responseMessage = 'User '. $user->email. ' was previously activated.';
            }else{
                $previousPermission = $user->permission;
                $newPermission = $user->previous_permission;

                $user->previous_permission = $previousPermission;
                $user->permission          = $newPermission;
                $saved                     = $user->save();

                if ($saved) {
                    $responseMessage = 'User account for '. $user->email. ' has been activated.';
                }else{
                    $responseMessage = 'Failed to activate the account of '. $user->email. '. Please try again.';
                }
            }

        }

        return redirect('/admin/users')->with('responseMessage', $responseMessage);
    }

    public function bulkDelete(Request $request)
    {
        $selectedIDs = $request->input('selectedIDs');
        $loggedInUser = auth()->user();
        $responseMessage = '';

        // if nothing is selected just return
        if ($selectedIDs == null) {
            return back();
        }

        foreach ($selectedIDs as $key => $id) {
            $user = User::find($id);

            if ($user) {
                if ($user->id == $loggedInUser->id) {
                    $responseMessage .= 'You cannot delete currently logged in account.';
                    $responseMessage .= '</br>';
                }else{
                    $user->delete();
                    $responseMessage .= 'User ' . $user->name . ' has been deleted.';
                    $responseMessage .= '</br>';
                }

            }else{
                $responseMessage .= 'User with ID: '. $id . 'is not found.';
                $responseMessage .= '</br>';
            }
        }

        return back()->with('responseMessage', $responseMessage);

    }

    public function bulkSuspend(Request $request)
    {
        $selectedIDs = $request->input('selectedIDs');
        $loggedInUser = auth()->user();
        $responseMessage = '';

        // if nothing is selected just return
        if ($selectedIDs == null) {
            return back();
        }

        foreach ($selectedIDs as $key => $id) {
            $user = User::find($id);

            if ($user) {
                if ($user->id == $loggedInUser->id) {
                    $responseMessage .= 'You cannot suspend currently logged in account.';
                    $responseMessage .= '</br>';
                }else{
                    // if user was already suspended then just do nothing
                    if ($user->permission == 0) {
                        $responseMessage .= 'User ' . $user->name . ' account was previously suspended.';
                        $responseMessage .= '</br>';
                    }else{
                        $user->previous_permission = $user->permission;
                        $user->permission          = 0;

                        $saved = $user->save();

                        if ($saved) {
                            $responseMessage .= 'User ' . $user->name . ' account has been suspended.';
                            $responseMessage .= '</br>';
                        }else{
                            $responseMessage .= 'Failed to suspend user account ' . $user->name . '. Please try again.';
                            $responseMessage .= '</br>';
                        }
                    }

                }

            }else{
                $responseMessage .= 'User with ID: '. $id . 'is not found.';
                $responseMessage .= '</br>';
            }
        }

        return back()->with('responseMessage', $responseMessage);

    }
}
