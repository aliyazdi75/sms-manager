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
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;

class SMSController extends AbstractController
{

    /**
     * @Route("/sms_list", name="sms_list")
     * @Method({"GET"})
     */
    public function index()
    {

        $sms = $this->getDoctrine()->getRepository(Sms::class)->findAll();

        return $this->render('sms/index.html.twig', array('sms' => $sms));
    }

    /**
     * @Route("/sms/new", name="new_sms")
     * Method({"GET", "POST"})
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function new(Request $request)
    {
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

        if ($form->isSubmitted() && $form->isValid()) {
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
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function show($id)
    {
        $sms = $this->getDoctrine()->getRepository(Sms::class)->find($id);

        return $this->render('sms/show.html.twig', array('sms' => $sms));
    }

    function CallAPI($method, $url, $data = false)
    {

        try {
            $ch = curl_init();

            // Check if initialization had gone wrong*
            if ($ch === false) {
                throw new Exception('failed to initialize');
            }

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

            $content = curl_exec($ch);

            // Check the return value of curl_exec(), too
            if ($content === false) {
                throw new Exception(curl_error($ch), curl_errno($ch));
            }

            /* Process $content here */

            // Close curl handle
            curl_close($ch);
        } catch (Exception $e) {

            trigger_error(sprintf(
                'Curl failed with error #%d: %s',
                $e->getCode(), $e->getMessage()),
                E_USER_ERROR);

        }
    }
}