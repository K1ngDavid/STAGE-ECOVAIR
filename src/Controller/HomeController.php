<?php

namespace App\Controller;

use App\Service\CallApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

//#[IsGranted('ROLE_COMMERCIAL')]
class HomeController extends AbstractController
{
    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface
     */

    #[Route('/', name: 'app_home')]
    public function index(CallApiService $client,Request $request,MailerInterface $mailer): Response
    {
//        dd("hello");
//        dd($this->getUser()->getRoles());
        if($this->getUser() == null) return $this->redirectToRoute("app_login");
        elseif($this->getUser()->getRoles()[0] == "ROLE_ADMIN") return $this->redirectToRoute('app_admin');
        $deals = $client->getDeals()['data'];
        $allDeals = $client->getDealsByNameCommercialAndByStatus($this->getUser()->getPrenom(). ' ' . $this->getUser()->getNom());
//        dd($allDeals);
        $nb_deals_valide = sizeof($allDeals['validé']);
        $nb_deals_retractes = sizeof($allDeals['rétracté']);
        $nb_total = sizeof($allDeals['en cours']) +$nb_deals_retractes + $nb_deals_valide;

        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
            'user' => $this->getUser(),
            'valide' => $nb_deals_valide,
            'retracte' => $nb_deals_retractes,
            'en_cours' => sizeof($allDeals['en cours']),
            'nb_total' =>$nb_total,
        ]);
    }

    #[Route('/deals', name: 'app_deals')]
    public function deals(CallApiService $client): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
//        dd($client->getDealsByNameCommercial($this->getUser()->getPrenom(). ' ' . $this->getUser()->getNom()));
        $deals = $client->getDealsByNameCommercial($this->getUser()->getPrenom(). ' ' . $this->getUser()->getNom());
        $dealss = $client->getDealsByNameCommercialAndByStatus($this->getUser()->getPrenom(). ' ' . $this->getUser()->getNom());
//        dd($deals);
//        dd($this->getUser()->getNom());
        return $this->render('deals/index.html.twig', [
            'controller_name' => 'DealsController',
            'deals' => $deals,
            'valide' => $dealss['validé'],
            'en_cours' => $dealss['en cours'],
            'refuse' => $dealss['rétracté']
        ]);
    }
    #[Route('/documents', name: 'app_docs')]
    public function documents(CallApiService $client,Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
//        dd($client->getDealsByNameCommercial($this->getUser()->getPrenom(). ' ' . $this->getUser()->getNom()));
        $deals = $client->getDealsByNameCommercial($this->getUser()->getPrenom(). ' ' . $this->getUser()->getNom());
        $dealss = $client->getDealsByNameCommercialAndByStatus($this->getUser()->getPrenom(). ' ' . $this->getUser()->getNom());
        if(isset($_FILES['file']['tmp_name'])){
//            dd($_FILES);
//            dd($_FILES['file']);
//            dd($request->request->get('file'));
//            dd($client->finalInsertFile($request->request->get('idDeal'))['data'][0]['message']);
            $response = $client->finalInsertFile($request->request->get('idDeal'))['data'][0]['message'];
            $this->addFlash('success',$response);
            return $this->render('deals/index.html.twig',[
            'controller_name' => 'DealsController',
            'deals' => $deals,
            'valide' => $dealss['validé'],
            'en_cours' => $dealss['en cours'],
            'refuse' => $dealss['rétracté']
            ]);
        }
//        dd($request);
//        dd($deals);
//        dd($this->getUser()->getNom());

//        return $this->render('deals/index.html.twig', [
//            'controller_name' => 'DocsController'
//            ]);
        return new Response();
    }

    #[Route('/profil','app_profil')]
    public function profil()
    {
        return $this->render('home/profil.html.twig',[
            'controller_name' => 'ProfilController',
//            'nom' => $this->getUser()->getNom(),
//            'prenom' => $this->getUser()->getPrenom(),
        ]);
    }
}
