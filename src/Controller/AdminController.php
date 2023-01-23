<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Service\CallApiService;
use com\zoho\crm\api\users\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[isGranted('ROLE_ADMIN')]
#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('/commerciaux', name: 'app_admin')]
    public function index(UserRepository $userRepository, Request $request,EntityManagerInterface $em,CallApiService $client): Response
    {
        if($request->request->get("nom-complet") != null){
            dd($request->request->get("nom-complet"));
        }
        if($request->request->get('insertEmail')){
            $nom_complet =  explode(" ",$request->request->get("nom-complet-input"));
            $user = new \App\Entity\User();
            $user->setEmail($request->request->get('insertEmail'));
            $user->setPrenom($nom_complet[0]);
            $user->setNom($nom_complet[1]);
            $em->persist($user);
            $em->flush();
            $this->addFlash('success',"L'utilisateur a été ajouté !");
        }

        $users = $userRepository->findAll();
        return $this->render('admin/index.html.twig', [
            'controller_name' => 'AdminController',
            'users' => $users,
            'noms' => $userRepository->getCommerciauxNotRegisteredYet(),
        ]);
    }
    #[Route('/', name: 'app_admin_dashboard')]
    public function dashboard(CallApiService $client): Response
    {
        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
            'user' => $this->getUser(),
            'nb_commerciaux' => sizeof($client->getAllCommerciaux()),
            'nb_contrats' => sizeof($client->getNbContrats()['validé']) + sizeof($client->getNbContrats()['rétracté']) + sizeof($client->getNbContrats()['en cours']),
            'nb_contrats_en_cours' => sizeof($client->getNbContrats()['en cours']),
            'nb_contrats_valides' => sizeof($client->getNbContrats()['validé']),
            'nb_contrats_retractes' => sizeof($client->getNbContrats()['rétracté'])
        ]);
    }

    #[Route('/commercial/{id}')]
    public function showCommercial(int $id,UserRepository $userRepository,CallApiService $client): Response
    {
        $user = $userRepository->findOneBy(['id' => $id]);
        $deals = $client->getDealsByNameCommercial($user->getPrenom(). ' ' . $user->getNom());
        $dealss = $client->getDealsByNameCommercialAndByStatus($user->getPrenom(). ' ' . $user->getNom());
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
//
}
