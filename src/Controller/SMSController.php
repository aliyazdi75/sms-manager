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
use http\Message\Body;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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

            $apiUrl1 = "http://localhost:81/?number=" . urlencode($sms->getNumber()) . "/send/sms&body="
                . urlencode($sms->getBody());
            $apiUrl2 = "http://localhost:82/?number=" . urlencode($sms->getNumber()) . "/send/sms&body="
                . urlencode($sms->getBody());
            try {
                try {
                    $sms->setStatus('sending by api1');
                    $entityManager = $this->getDoctrine()->getManager();
                    $entityManager->persist($sms);
                    $entityManager->flush();
                    $this->CallAPI($apiUrl1);
                    $sms->setStatus('Sent by api1');
                    return $this->redirectToRoute('sms_list');
                } catch (Exception $e) {
                    $sms->setStatus('sending by api2');
                    $entityManager = $this->getDoctrine()->getManager();
                    $entityManager->persist($sms);
                    $entityManager->flush();
                    $this->CallAPI($apiUrl2);
                    $sms->setStatus('Sent by api2');
                    return $this->redirectToRoute('sms_list');
                }
            } catch (Exception $e) {
                $sms->setStatus('sending later!');
            }
        }

        return $this->render('sms/new.html.twig', array(
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/sms/report", name="sms_report")
     * @return Response
     */
    public function report()
    {
        $sms = $this->getDoctrine()->getRepository(Sms::class);
        return $this->render('sms/report.html.twig', array('sms' => $sms));
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

    /**
     * @Route("/number={number}/send/sms&body={body}", name="new_sms_api")
     * Method({"GET", "POST"})
     * @param $number
     * @param $body
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function RestAPI($number, $body)
    {
        $sms = new Sms();
        $sms->setNumber($number);
        $sms->setBody($body);
        $apiUrl1 = "http://localhost:81/?number=" . urlencode($number) . "/send/sms&body=" . urlencode($body);
        $apiUrl2 = "http://localhost:82/?number=" . urlencode($number) . "/send/sms&body=" . urlencode($body);
        try {
            try {
                $sms->setStatus('sending by api1');
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($sms);
                $entityManager->flush();
                $this->CallAPI($apiUrl1);
                $sms->setStatus('Sent by api1');
                return $this->redirectToRoute('sms_list');
            } catch (Exception $e) {
                $sms->setStatus('sending by api2');
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($sms);
                $entityManager->flush();
                $this->CallAPI($apiUrl2);
                $sms->setStatus('Sent by api2');
                return $this->redirectToRoute('sms_list');
            }
        } catch (Exception $e) {
            $sms->setStatus('sending later!');
        }
    }

    /**
     * curl get for Rest api
     * @param $url
     * @return bool|string
     */
    public function CallAPI($url)
    {

        $ch = curl_init();

        // Check if initialization had gone wrong*
        if ($ch === false) {
            throw new Exception('failed to initialize');
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $content = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new Exception(curl_error($ch));
        }

        // Close curl handle
        curl_close($ch);
        return $content;
    }
}