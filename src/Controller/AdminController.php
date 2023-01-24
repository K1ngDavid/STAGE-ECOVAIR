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
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

#[isGranted('ROLE_ADMIN')]
#[Route('/admin')]
class AdminController extends AbstractController
{
    private $client = null;

    #[Route('/commerciaux', name: 'app_admin')]
    public function index(UserRepository $userRepository, Request $request,EntityManagerInterface $em): Response
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

    /**
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     */
    #[Route('/', name: 'app_admin_dashboard')]
    public function dashboard(): Response
    {
        if(!$this->client instanceof CallApiService) $this->client = new CallApiService();
        $contrats = $this->client->getNbContrats();
        $deals = $this->client->getTauxDeConversion();
//        $commerciaux = $this->client->getBestCommerciaux();

        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
            'user' => $this->getUser(),
            'nb_commerciaux' => sizeof($this->client->getAllCommerciaux()),
            'nb_contrats' => (sizeof($contrats['validé']) + sizeof($contrats['rétracté']) + sizeof($contrats['en cours'])),
            'nb_contrats_en_cours' => sizeof($contrats['en cours']),
            'nb_contrats_valides' => sizeof($contrats['validé']),
            'nb_contrats_retractes' => sizeof($contrats['rétracté']),
            'signatures' => $deals['signatures'],
            'installations' => $deals['installations']
        ]);
    }

    #[Route('/commercial/{id}')]
    public function showCommercial(int $id,UserRepository $userRepository,CallApiService $client): Response
    {
        $user = $userRepository->findOneBy(['id' => $id]);
        $deals = $client->getDealsByNameCommercial($user->getPrenom(). ' ' . $user->getNom());
        $dealss = $client->getDealsByNameCommercialAndByStatus($user->getPrenom(). ' ' . $user->getNom());

        return $this->render('deals/index.html.twig', [
            'controller_name' => 'DealsController',
            'deals' => $deals,
            'valide' => $dealss['validé'],
            'en_cours' => $dealss['en cours'],
            'refuse' => $dealss['rétracté']
        ]);
    }
    #[Route('/commercial/del/{id}','app_del_commercial')]
    public function deleteCommercial(int $id,UserRepository $userRepository)
    {
        $this->addFlash('success',"L'utilisateur {$userRepository->find($id)->getEmail()} a bien été supprimé");
        $userRepository->delCommercial($id);
        return $this->redirectToRoute('app_admin');
    }
}
