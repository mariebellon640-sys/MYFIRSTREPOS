<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Enum\Langue;
use App\EventSubscriber\LocaleSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class LangueController extends AbstractController
{
    #[Route('/langue/{langue}', name: 'app_langue', requirements: ['langue' => 'fr|mg'], methods: ['GET'])]
    public function changer(string $langue, Request $request, EntityManagerInterface $em): RedirectResponse
    {
        $request->getSession()->set(LocaleSubscriber::CLE_SESSION, $langue);

        $utilisateur = $this->getUser();
        if ($utilisateur instanceof Utilisateur) {
            $utilisateur->setLanguePreferee('mg' === $langue ? Langue::MG : Langue::FR);
            $em->flush();
        }

        return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('app_accueil'));
    }
}
