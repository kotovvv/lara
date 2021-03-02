<?php

namespace App\Http\Controllers;

use View;
use Request;
use Barryvdh\Debugbar\Facade as Debugbar;
use Auth;
use Redirect;

class HomeController extends Controller
{
    // app/controllers/HomeController.php<


    public function showLogin()
    {
        // show the form
        return View::make('login');
    }

    public function doLogin(Request $request)
    {
        $rules = array(
            'name'    => 'required|min:3', // make sure the email is an actual email
            'password' => 'required|alphaNum|min:3' // password can only be alphanumeric and has to be greater than 3 characters
        );

        $credentials = Request::only('name', 'password');

        if (!Auth::once($credentials)) {

            return Redirect::to('/')
                ->withErrors(['error' => 'Check name/password and try']) // send back all errors to the login form
                ->withInput($request::except('password'));
        } else {
            $user = Auth::getUser();
            $roleName = $user->getRoleName($user);
            echo $roleName;
        }
    }
}
