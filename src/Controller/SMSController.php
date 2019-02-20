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
     * @Route("/list/sms", name="sms_list")
     * @Method({"GET"})
     */
    public function index()
    {

        $sms = $this->getDoctrine()->getRepository(Sms::class)->findAll();

        return $this->render('sms/index.html.twig', array('sms' => $sms));
    }

    /**
     * @Route("/new/sms", name="new_sms")
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
        $entityManager = $this->getDoctrine()->getManager();

        if ($form->isSubmitted() && $form->isValid()) {
            $sms = $form->getData();
            $sms->setApi1Count(0);
            $sms->setApi2Count(0);
            $entityManager->persist($sms);
            $this->SendingProcess($sms, $entityManager);
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

    /**
     * @Route("/search/sms/{number}", name="sms_search")
     * @param $number
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function search($number)
    {
        $sms = $this->getDoctrine()->getRepository(Sms::class)->findBy(['number' => $number]);

        return $this->render('sms/search.html.twig', array('sms' => $sms));
    }

    /**
     * @Route("/report/sms", name="sms_report")
     * @return Response
     */
    public function report()
    {
        $sms = $this->getDoctrine()->getRepository(Sms::class);
        $total_sms_count = count($sms->findBy(['api_sent' => 1 or 2]));
        $api1_sms_count = count($sms->findBy(['api_sent' => 1]));
        $api2_sms_count = count($sms->findBy(['api_sent' => 2]));
        $Api1Count = 0;
        $Api2Count = 0;
        foreach ($sms->findAll() as $s) {
            $Api1Count += $s->getApi1Count();
            $Api2Count += $s->getApi2Count();
        }
        $unsuccessful_api1_sms_count = (1 - ($api1_sms_count) / ($Api1Count)) * 100;
        $unsuccessful_api2_sms_count = (1 - ($api2_sms_count) / ($Api2Count)) * 100;
        $most_10number = $sms->findMostRepeatedNumber();

        return $this->render('sms/report.html.twig', array(
            'total_sms_count' => $total_sms_count,
            'api1_sms_count' => $api1_sms_count,
            'api2_sms_count' => $api2_sms_count,
            'unsuccessful_api1_sms_count' => $unsuccessful_api1_sms_count,
            'unsuccessful_api2_sms_count' => $unsuccessful_api2_sms_count,
            'most_10number' => $most_10number
        ));
    }

    /**
     * @Route("/number={number}/send/sms&body={body}", name="new_sms_api")
     * Method({"GET", "POST"})
     * @param $number
     * @param $body
     */
    public function RestAPI($number, $body)
    {
        $sms = new Sms();
        $sms->setNumber($number);
        $sms->setBody($body);
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($sms);
        $this->SendingProcess($sms, $entityManager);
    }

    /**
     * @param $sms
     * @param $entityManager
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function SendingProcess($sms, $entityManager)
    {
        $apiUrl1 = "http://localhost:81/?number=" . urlencode($sms->getNumber()) . "/send/sms&body="
            . urlencode($sms->getBody());
        $apiUrl2 = "http://localhost:82/?number=" . urlencode($sms->getNumber()) . "/send/sms&body="
            . urlencode($sms->getBody());
        try {
            try {
                $sms->setApi1Count($sms->getApi1Count() + 1);
                $sms->setStatus('sending by api1');
                $entityManager->flush();
                $this->CallAPI($apiUrl1);
                $sms->setStatus('Sent by api1');
                $sms->setApiSent(1);
                $entityManager->flush();
            } catch (Exception $e) {
                $sms->setApi2Count($sms->getApi2Count() + 1);
                $sms->setStatus('sending by api2');
                $entityManager->flush();
                $this->CallAPI($apiUrl2);
                $sms->setStatus('Sent by api2');
                $sms->setApiSent(2);
                $entityManager->flush();
            }
        } catch (Exception $e) {
            $sms->setStatus('sending later!');
        }
        return $this->redirectToRoute('sms_list');
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