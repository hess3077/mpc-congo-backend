<?php

Class HomeController extends BaseController
{

    public function welcome()
    {
        $this->data['title'] = "MPC Congo : Espace d'administration du portail";
        App::render('welcome.twig', $this->data);
    }
}