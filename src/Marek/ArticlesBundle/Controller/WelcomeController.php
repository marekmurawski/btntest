<?php

namespace Marek\ArticlesBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class WelcomeController extends Controller
{
    public function indexAction()
    {
        return $this->render('MarekArticlesBundle::welcome.html.twig');
    }
}
