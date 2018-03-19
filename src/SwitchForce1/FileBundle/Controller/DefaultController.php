<?php

namespace SwitchForce1\FileBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('SwitchF1FileBundle:Default:index.html.twig');
    }
}
