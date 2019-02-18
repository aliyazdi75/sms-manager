<?php
/**
 * Created by PhpStorm.
 * User: Ali
 * Date: 18/02/2019
 * Time: 04:37 PM
 */

namespace App\Controller;


use App\Entity\Sms;
use FOS\RestBundle\Controller\Annotations\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class SMSController extends AbstractController {

    /**
     * @Route("/sms_list", name="sms_list")
     * @Method({"GET"})
     */
    public function index() {

        $articles= $this->getDoctrine()->getRepository(Sms::class)->findAll();

        return $this->render('sms/index.html.twig', array('articles' => $articles));
    }

    /**
     * @Route("/sms/new", name="new_sms")
     * Method({"GET", "POST"})
     */
    public function new(Request $request) {
        $sms = new Sms();

        $form = $this->createFormBuilder($sms)
            ->add('number', TextType::class, array(
                'required' => true,
                'attr' => array('class' => 'form-control')
            ))
            ->add('body', TextareaType::class, array(
                'required' => false,
                'attr' => array('class' => 'form-control')
            ))
            ->add('send', SubmitType::class, array(
                'label' => 'Send',
                'attr' => array('class' => 'btn btn-primary mt-3')
            ))
            ->getForm();

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $sms = $form->getData();

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($sms);
            $entityManager->flush();

            return $this->redirectToRoute('sms_list');
        }

        return $this->render('sms/new.html.twig', array(
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/sms/{id}", name="sms_show")
     */
    public function show($id) {
        $article = $this->getDoctrine()->getRepository(Article::class)->find($id);

        return $this->render('sms/show.html.twig', array('article' => $article));
    }

    function CallAPI($method, $url, $data = false)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new Exception(curl_error($ch));
        }
        curl_close($ch);
        return $result;
    }
}