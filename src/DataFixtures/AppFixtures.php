<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Adresse;
use App\Entity\Avis;
use App\Entity\CategorieProduit;
use App\Entity\Client;
use App\Entity\CodePromo;
use App\Entity\Fournisseur;
use App\Entity\Livreur;
use App\Entity\LotPeche;
use App\Entity\Produit;
use App\Entity\Utilisateur;
use App\Entity\ZoneLivraison;
use App\Enum\ModePaiement;
use App\Enum\Role;
use App\Enum\StatutCommande;
use App\Enum\StatutDisponibilite;
use App\Enum\StatutModeration;
use App\Enum\StatutValidation;
use App\Enum\TypeFournisseur;
use App\Enum\TypeReduction;
use App\Enum\TypeVehicule;
use App\Enum\UniteProduit;
use App\Service\CommandeService;
use App\Service\FraicheurCalculateur;
use App\Service\PanierService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Jeu de donnees de demonstration : zones d'Antananarivo, categories de produits de la mer,
 * comptes des quatre profils, arrivages traces et quelques commandes deja livrees.
 */
class AppFixtures extends Fixture
{
    private const MOT_DE_PASSE = 'Poisson2024!';

    public function __construct(
        private readonly UserPasswordHasherInterface $hasher,
        private readonly FraicheurCalculateur $fraicheur,
        private readonly PanierService $panierService,
        private readonly CommandeService $commandeService,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $zones = $this->chargerZones($manager);
        $categories = $this->chargerCategories($manager);

        $administrateur = $this->creerUtilisateur('Rakoto', 'Hery', 'admin@poissonvip.mg', '+261340000001', Role::ADMIN);
        $support = $this->creerUtilisateur('Randria', 'Miora', 'support@poissonvip.mg', '+261340000002', Role::SUPPORT);
        $manager->persist($administrateur);
        $manager->persist($support);

        $fournisseurs = $this->chargerFournisseurs($manager, $zones);
        $livreurs = $this->chargerLivreurs($manager, $zones);
        $clients = $this->chargerClients($manager, $zones);
        $produits = $this->chargerProduits($manager, $fournisseurs, $categories);
        $this->chargerPromotions($manager);

        $manager->flush();

        $this->chargerCommandes($manager, $clients, $produits, $livreurs);

        $manager->flush();
    }

    /** @return array<string, ZoneLivraison> */
    private function chargerZones(ObjectManager $manager): array
    {
        $definitions = [
            ['Analakely', 'Antananarivo I', '3000.00', 45],
            ['Ankorondrano', 'Antananarivo I', '3500.00', 40],
            ['Ivandry', 'Antananarivo I', '4500.00', 50],
            ['Andoharanofotsy', 'Antananarivo Atsimondrano', '6000.00', 75],
            ['Talatamaty', 'Ambohidratrimo', '6500.00', 80],
        ];

        $zones = [];
        foreach ($definitions as [$nom, $commune, $frais, $delai]) {
            $zone = new ZoneLivraison();
            $zone->setNomZone($nom)->setCommune($commune)->setFraisLivraison($frais)->setDelaiEstimeMinutes($delai);
            $manager->persist($zone);
            $zones[$nom] = $zone;
        }

        return $zones;
    }

    /** @return array<string, CategorieProduit> */
    private function chargerCategories(ObjectManager $manager): array
    {
        $definitions = [
            'Poissons de mer' => 'Especes pechees le long des cotes malgaches.',
            "Poissons d'eau douce" => 'Especes issues des lacs et rivieres des Hautes Terres.',
            'Crustaces' => 'Crevettes, langoustes et crabes.',
            'Mollusques' => 'Calmars, poulpes et coquillages.',
            'Produits transformes' => 'Poissons fumes, seches ou prepares.',
        ];

        $categories = [];
        foreach ($definitions as $nom => $description) {
            $categorie = new CategorieProduit();
            $categorie->setNom($nom)->setDescription($description);
            $manager->persist($categorie);
            $categories[$nom] = $categorie;
        }

        return $categories;
    }

    /**
     * @param array<string, ZoneLivraison> $zones
     *
     * @return list<Fournisseur>
     */
    private function chargerFournisseurs(ObjectManager $manager, array $zones): array
    {
        $definitions = [
            ['Randriamampionona', 'Fanja', 'fanja@mareyeur-tana.mg', '+261340000010', 'Mareyeur Tana Fresh', TypeFournisseur::MAREYEUR, 'Ankorondrano', StatutValidation::VALIDE],
            ['Rasoanaivo', 'Tojo', 'tojo@cooperative-mahajanga.mg', '+261340000011', 'Cooperative de Mahajanga', TypeFournisseur::COOPERATIVE, 'Analakely', StatutValidation::VALIDE],
            ['Andrianina', 'Lova', 'lova@poissonnerie-ivandry.mg', '+261340000012', "Poissonnerie d'Ivandry", TypeFournisseur::POISSONNERIE, 'Ivandry', StatutValidation::VALIDE],
            ['Rabemananjara', 'Naina', 'naina@peche-toamasina.mg', '+261340000013', 'Peche Toamasina', TypeFournisseur::MAREYEUR, 'Talatamaty', StatutValidation::EN_ATTENTE],
        ];

        $fournisseurs = [];
        foreach ($definitions as [$nom, $prenom, $email, $telephone, $enseigne, $type, $zone, $statut]) {
            $utilisateur = $this->creerUtilisateur($nom, $prenom, $email, $telephone, Role::FOURNISSEUR);
            $fournisseur = new Fournisseur($utilisateur);
            $fournisseur
                ->setNomCommercial($enseigne)
                ->setTypeFournisseur($type)
                ->setZoneLivraison($zones[$zone])
                ->setStatutValidation($statut)
                ->setNoteMoyenne('4.50');

            $manager->persist($utilisateur);
            $manager->persist($fournisseur);
            $fournisseurs[] = $fournisseur;
        }

        return $fournisseurs;
    }

    /**
     * @param array<string, ZoneLivraison> $zones
     *
     * @return list<Livreur>
     */
    private function chargerLivreurs(ObjectManager $manager, array $zones): array
    {
        $definitions = [
            ['Rakotomalala', 'Tiana', 'tiana@poissonvip.mg', '+261340000020', TypeVehicule::MOTO, 'Ankorondrano', '-18.8776', '47.5253'],
            ['Ratsimba', 'Fenohery', 'feno@poissonvip.mg', '+261340000021', TypeVehicule::MOTO, 'Analakely', '-18.9083', '47.5250'],
            ['Razafindrakoto', 'Mamy', 'mamy@poissonvip.mg', '+261340000022', TypeVehicule::VOITURE, 'Ivandry', '-18.8592', '47.5312'],
        ];

        $livreurs = [];
        foreach ($definitions as [$nom, $prenom, $email, $telephone, $vehicule, $zone, $latitude, $longitude]) {
            $utilisateur = $this->creerUtilisateur($nom, $prenom, $email, $telephone, Role::LIVREUR);
            $livreur = new Livreur($utilisateur);
            $livreur
                ->setTypeVehicule($vehicule)
                ->setNumeroPermis('PERM-'.random_int(10000, 99999))
                ->setZoneAffectation($zones[$zone])
                ->setStatutDisponibilite(StatutDisponibilite::DISPONIBLE)
                ->setPositionActuelle($latitude, $longitude);

            $manager->persist($utilisateur);
            $manager->persist($livreur);
            $livreurs[] = $livreur;
        }

        return $livreurs;
    }

    /**
     * @param array<string, ZoneLivraison> $zones
     *
     * @return list<Client>
     */
    private function chargerClients(ObjectManager $manager, array $zones): array
    {
        $definitions = [
            ['Rasolofo', 'Hasina', 'hasina@example.mg', '+261340000030', 'Domicile', 'Ankorondrano', 'Rue Ravoninahitriniarivo', '-18.8776', '47.5253'],
            ['Andriamalala', 'Voahirana', 'voahirana@example.mg', '+261340000031', 'Bureau', 'Analakely', 'Avenue de l\'Independance', '-18.9083', '47.5250'],
            ['Rakotoarisoa', 'Nirina', 'nirina@example.mg', '+261340000032', 'Domicile', 'Andoharanofotsy', 'Route Nationale 7', '-18.9772', '47.5222'],
        ];

        $clients = [];
        foreach ($definitions as [$nom, $prenom, $email, $telephone, $libelle, $zone, $rue, $latitude, $longitude]) {
            $utilisateur = $this->creerUtilisateur($nom, $prenom, $email, $telephone, Role::CLIENT);
            $client = new Client($utilisateur);
            $client->setPointsFidelite(random_int(0, 120));

            $adresse = new Adresse();
            $adresse
                ->setLibelle($libelle)
                ->setQuartier($zone)
                ->setRue($rue)
                ->setPointRepere('A cote de l\'epicerie du quartier')
                ->setZoneLivraison($zones[$zone])
                ->setLatitude($latitude)
                ->setLongitude($longitude)
                ->setEstPrincipale(true);
            $utilisateur->addAdresse($adresse);

            $manager->persist($utilisateur);
            $manager->persist($client);
            $manager->persist($adresse);
            $clients[] = $client;
        }

        return $clients;
    }

    /**
     * @param list<Fournisseur>              $fournisseurs
     * @param array<string, CategorieProduit> $categories
     *
     * @return list<Produit>
     */
    private function chargerProduits(ObjectManager $manager, array $fournisseurs, array $categories): array
    {
        $definitions = [
            ['Thon jaune', 'Thunnus albacares', 'Poissons de mer', '28000.00', UniteProduit::KG, 'Entier,Darne,Filet', 45.0, 'Nosy Be', 6, 0],
            ['Capitaine', 'Lates calcarifer', 'Poissons de mer', '32000.00', UniteProduit::KG, 'Entier,Filet', 30.0, 'Mahajanga', 10, 0],
            ['Crevettes roses', 'Penaeus indicus', 'Crustaces', '45000.00', UniteProduit::KG, 'Entieres,Decortiquees', 18.0, 'Morondava', 14, 1],
            ['Langouste', 'Panulirus homarus', 'Crustaces', '85000.00', UniteProduit::KG, 'Entiere', 8.0, 'Fort-Dauphin', 20, 1],
            ['Calmar', 'Loligo duvauceli', 'Mollusques', '26000.00', UniteProduit::KG, 'Entier,Anneaux', 22.0, 'Toliara', 12, 2],
            ['Tilapia', 'Oreochromis niloticus', "Poissons d'eau douce", '14000.00', UniteProduit::KG, 'Entier,Vide', 60.0, 'Lac Itasy', 5, 2],
            ['Carpe royale', 'Cyprinus carpio', "Poissons d'eau douce", '12000.00', UniteProduit::KG, 'Entiere,Vide', 40.0, 'Lac Alaotra', 8, 1],
            ['Poisson fume', 'Sardinella gibbosa', 'Produits transformes', '18000.00', UniteProduit::KG, 'Sachet', 25.0, 'Toamasina', 30, 0],
        ];

        $produits = [];
        foreach ($definitions as [$nom, $espece, $categorie, $prix, $unite, $preparations, $stock, $lieu, $heures, $indexFournisseur]) {
            $produit = new Produit();
            $produit
                ->setFournisseur($fournisseurs[$indexFournisseur])
                ->setCategorie($categories[$categorie])
                ->setNom($nom)
                ->setEspece($espece)
                ->setDescription(sprintf('%s peche a %s et livre sous chaine du froid.', $nom, $lieu))
                ->setPrixUnitaire($prix)
                ->setUnite($unite)
                ->setPreparationsDispo($preparations)
                ->setStockDisponible(number_format($stock, 2, '.', ''))
                ->setEstActif(true);

            $lot = new LotPeche();
            $lot
                ->setLieuPeche($lieu)
                ->setDateHeureCapture(new \DateTimeImmutable(sprintf('-%d hours', $heures)))
                ->setDateHeureMiseEnVente(new \DateTimeImmutable(sprintf('-%d hours', max(0, $heures - 4))))
                ->setQuantiteKg(number_format($stock * 1.5, 2, '.', ''));
            $this->fraicheur->actualiser($lot);
            $produit->addLot($lot);

            $manager->persist($produit);
            $manager->persist($lot);
            $produits[] = $produit;
        }

        return $produits;
    }

    private function chargerPromotions(ObjectManager $manager): void
    {
        $bienvenue = new CodePromo();
        $bienvenue
            ->setCode('BIENVENUE10')
            ->setTypeReduction(TypeReduction::POURCENTAGE)
            ->setValeur('10.00')
            ->setUtilisationMax(200);

        $livraison = new CodePromo();
        $livraison
            ->setCode('FRAIS5000')
            ->setTypeReduction(TypeReduction::MONTANT_FIXE)
            ->setValeur('5000.00')
            ->setUtilisationMax(100);

        $manager->persist($bienvenue);
        $manager->persist($livraison);
    }

    /**
     * @param list<Client>  $clients
     * @param list<Produit> $produits
     * @param list<Livreur> $livreurs
     */
    private function chargerCommandes(ObjectManager $manager, array $clients, array $produits, array $livreurs): void
    {
        $scenarios = [
            [0, [[0, 2.0, 'Darne'], [5, 1.5, 'Vide']], ModePaiement::MVOLA, StatutCommande::LIVREE, 5],
            [1, [[2, 1.0, 'Decortiquees']], ModePaiement::ORANGE_MONEY, StatutCommande::EN_LIVRAISON, 4],
            [2, [[4, 2.0, 'Anneaux'], [7, 1.0, 'Sachet']], ModePaiement::ESPECES_LIVRAISON, StatutCommande::EN_PREPARATION, 3],
        ];

        foreach ($scenarios as $index => [$indexClient, $lignes, $mode, $statutFinal, $note]) {
            $client = $clients[$indexClient];
            $panier = null;
            foreach ($lignes as [$indexProduit, $quantite, $preparation]) {
                $panier = $this->panierService->ajouterProduit($client, $produits[$indexProduit], number_format($quantite, 2, '.', ''), $preparation);
            }

            $adresse = $client->getUtilisateur()->getAdressePrincipale();
            if (null === $adresse || null === $panier) {
                continue;
            }

            $commande = $this->commandeService->creerDepuisPanier(
                $panier,
                $adresse,
                $mode,
                new \DateTimeImmutable('tomorrow 09:00'),
            );

            if (ModePaiement::ESPECES_LIVRAISON !== $mode) {
                $this->commandeService->confirmerApresPaiement($commande);
            }

            $this->commandeService->changerStatut($commande, $statutFinal);

            $livraison = $commande->getLivraison();
            if (null !== $livraison) {
                $livraison->setLivreur($livreurs[$index % \count($livreurs)]);
            }

            if (StatutCommande::LIVREE === $statutFinal) {
                $avis = new Avis($client, $commande);
                $avis
                    ->setProduit($produits[$lignes[0][0]])
                    ->setNote($note)
                    ->setCommentaire('Poisson tres frais, livraison ponctuelle et bien emballee.')
                    ->setStatutModeration(StatutModeration::PUBLIE);
                $manager->persist($avis);
            }
        }
    }

    private function creerUtilisateur(string $nom, string $prenom, string $email, string $telephone, Role $role): Utilisateur
    {
        $utilisateur = new Utilisateur();
        $utilisateur
            ->setNom($nom)
            ->setPrenom($prenom)
            ->setEmail($email)
            ->setTelephone($telephone)
            ->setRole($role)
            ->setEstVerifie(true)
            ->setEstActif(true);
        $utilisateur->setMotDePasse($this->hasher->hashPassword($utilisateur, self::MOT_DE_PASSE));

        return $utilisateur;
    }
}
