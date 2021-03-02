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

        // run the validation rules on the inputs from the form
        //$validator = Validator::make(Input::all(), $rules);

        // if the validator fails, redirect back to the form
        //        if ($validator->fails()) {
        //            return Redirect::to('login')
        //                ->withErrors($validator) // send back all errors to the login form
        //                ->withInput(Input::except('password')); // send back the input (not the password) so that we can repopulate the form
        //        } else {

        // create our user data for the authentication
        //             $userdata = array(
        //                 'name'     => $request->input('name'),
        //                 'password'  => $request->input('password')

        //             );
        // echo 'Holla';
        // Debugbar::info($request->input());
        // $user = User::whereName($username)->wherePassword(Hash::make($password))->first();
        $credentials = Request::only('name', 'password');

        if (!Auth::once($credentials)) {
            Debugbar::info($credentials);
            return Redirect::to('/')
                ->withErrors(['error' => 'Check name/password and try']) // send back all errors to the login form
                ->withInput($request::except('password'));
        } else {
            $user = Auth::getUser();
            // Debugbar::info($user);
            echo 'SUCCESS!';
        }


        // attempt to do the login
        //    if (Auth::attempt($userdata)) {

        //         // validation successful!
        //         // redirect them to the secure section or whatever
        //         return Redirect::to('secure');
        //         //for now we'll just echo success (even though echoing in a controller is bad)
        //        echo 'SUCCESS!';

        //    } else {

        //         //validation not successful, send back to form
        //        return Redirect::to('login');

        //    }

    }

    //    }
}
