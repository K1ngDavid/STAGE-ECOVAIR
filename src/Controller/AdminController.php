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
    public function dashboard(UserRepository $userRepository, Request $request,EntityManagerInterface $em,CallApiService $client): Response
    {
        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
            'user' => $this->getUser(),
        ]);
    }
}
