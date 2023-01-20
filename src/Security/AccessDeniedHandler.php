<?php

// src/Security/AccessDeniedHandler.php
namespace App\Security;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;

class AccessDeniedHandler extends AbstractController implements AccessDeniedHandlerInterface
{
    private $content;

    public function handle(Request $request, AccessDeniedException $accessDeniedException): ?Response
    {
        // ...
        $content = "Droit refusé";
        $route = $request->headers->get('referer');
        return $this->render('security/access_denied.html.twig',[
            'error' => $content
        ]);
    }

    /**
     * @return mixed
     */
    public function getContent()
    {
        return $this->content;
    }

}