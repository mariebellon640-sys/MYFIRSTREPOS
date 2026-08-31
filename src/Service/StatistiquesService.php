<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Fournisseur;
use App\Enum\Role;
use App\Enum\StatutCommande;
use App\Enum\StatutValidation;
use App\Repository\CommandeRepository;
use App\Repository\FournisseurRepository;
use App\Repository\LigneCommandeRepository;
use App\Repository\ProduitRepository;
use App\Repository\UtilisateurRepository;

class StatistiquesService
{
    public function __construct(
        private readonly CommandeRepository $commandeRepository,
        private readonly UtilisateurRepository $utilisateurRepository,
        private readonly FournisseurRepository $fournisseurRepository,
        private readonly ProduitRepository $produitRepository,
        private readonly LigneCommandeRepository $ligneCommandeRepository,
    ) {
    }

    /** @return array<string, mixed> */
    public function tableauDeBordAdministrateur(): array
    {
        $nombreCommandes = $this->commandeRepository->compterHorsAnnulees();
        $chiffreAffaires = $this->commandeRepository->chiffreAffairesTotal();

        return [
            'chiffreAffaires' => $chiffreAffaires,
            'nombreCommandes' => $nombreCommandes,
            'panierMoyen' => 0 === $nombreCommandes ? '0.00' : number_format((float) $chiffreAffaires / $nombreCommandes, 2, '.', ''),
            'repartitionStatuts' => $this->commandeRepository->repartitionParStatut(),
            'produitsLesPlusVendus' => $this->commandeRepository->produitsLesPlusVendus(),
            'commandesParZone' => $this->commandeRepository->commandesParZone(),
            'nombreClients' => $this->utilisateurRepository->compterParRole(Role::CLIENT),
            'nombreFournisseurs' => $this->utilisateurRepository->compterParRole(Role::FOURNISSEUR),
            'nombreLivreurs' => $this->utilisateurRepository->compterParRole(Role::LIVREUR),
            'fournisseursEnAttente' => \count($this->fournisseurRepository->findParStatut(StatutValidation::EN_ATTENTE)),
        ];
    }

    /** @return array<string, mixed> */
    public function tableauDeBordFournisseur(Fournisseur $fournisseur): array
    {
        $ventes = $this->ventesParProduit($fournisseur);

        return [
            'chiffreAffaires' => $this->chiffreAffaires($fournisseur),
            'ventesParProduit' => $ventes,
            'alertesStock' => $this->produitRepository->findEnAlerteStock($fournisseur),
            'commandesEnCours' => $this->commandeRepository->findPourFournisseur($fournisseur, StatutCommande::EN_PREPARATION),
            'previsionDemande' => $this->previsionDemande($ventes),
        ];
    }

    public function chiffreAffaires(Fournisseur $fournisseur): string
    {
        $total = $this->ligneCommandeRepository->createQueryBuilder('l')
            ->select('SUM(l.sousTotal)')
            ->join('l.commande', 'c')
            ->where('l.fournisseur = :fournisseur')
            ->andWhere('c.statut = :livree')
            ->setParameter('fournisseur', $fournisseur)
            ->setParameter('livree', StatutCommande::LIVREE)
            ->getQuery()
            ->getSingleScalarResult();

        return number_format((float) $total, 2, '.', '');
    }

    /** @return list<array{nom: string, quantite: string, chiffreAffaires: string, joursActifs: int}> */
    public function ventesParProduit(Fournisseur $fournisseur): array
    {
        /** @var list<array{nom: string, quantite: string, chiffreAffaires: string, joursActifs: int}> $lignes */
        $lignes = $this->ligneCommandeRepository->createQueryBuilder('l')
            ->select('p.nom AS nom, SUM(l.quantite) AS quantite, SUM(l.sousTotal) AS chiffreAffaires, COUNT(DISTINCT SUBSTRING(c.dateCommande, 1, 10)) AS joursActifs')
            ->join('l.produit', 'p')
            ->join('l.commande', 'c')
            ->where('l.fournisseur = :fournisseur')
            ->andWhere('c.statut != :annulee')
            ->setParameter('fournisseur', $fournisseur)
            ->setParameter('annulee', StatutCommande::ANNULEE)
            ->groupBy('p.id')
            ->orderBy('quantite', 'DESC')
            ->getQuery()
            ->getResult();

        return $lignes;
    }

    /**
     * Prevision simple de la demande hebdomadaire : moyenne quotidienne observee
     * projetee sur sept jours, pour aider le fournisseur a dimensionner ses arrivages.
     *
     * @param list<array{nom: string, quantite: string, chiffreAffaires: string, joursActifs: int}> $ventes
     *
     * @return list<array{nom: string, previsionHebdomadaire: string}>
     */
    public function previsionDemande(array $ventes): array
    {
        $previsions = [];
        foreach ($ventes as $vente) {
            $jours = max(1, (int) $vente['joursActifs']);
            $moyenneQuotidienne = (float) $vente['quantite'] / $jours;
            $previsions[] = [
                'nom' => $vente['nom'],
                'previsionHebdomadaire' => number_format($moyenneQuotidienne * 7, 2, '.', ''),
            ];
        }

        return $previsions;
    }
}
