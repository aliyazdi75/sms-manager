<?php
/**
 * Created by PhpStorm.
 * User: Ali
 * Date: 17/02/2019
 * Time: 06:54 PM
 */

namespace App\Controller;


use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;

class SMSController
{
    /**
     * @Route("/")
     */
    public function Homepage(){
        return new Response('OMG! My first page already! WOOO!');
    }
}