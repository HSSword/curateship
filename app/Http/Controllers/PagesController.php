<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\User;

class PagesController extends Controller
{
    public function index(){
        return view('index');
    }

    public function mangas(){
        return view('pages.mangas');
    }

    public function blogpage(){
        return view('pages.blogpage');
    }

    public function about(){
        return view('pages.about');
    }

    public function contact(){
        return view('pages.contact');
    }

    public function venue(){
        return view('pages.venue');
    }

    public function profile(){
        return view('pages.profile');
    }

    public function blogadmin(){
        return view('pages.blogadmin');
    }

    public function admin2(){
        return view('pages.admin2');
    }

    public function adminlogin(){
        return view('pages.adminlogin');
    }

    public function useradmin(){
        return view('pages.useradmin');
    }

    public function admin(){
        return redirect('/admin/users');
    }
    
}
