<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260831075234 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE adresse (id INT UNSIGNED AUTO_INCREMENT NOT NULL, libelle VARCHAR(100) NOT NULL COMMENT \'Ex: Maison, Bureau\', quartier VARCHAR(150) NOT NULL, rue VARCHAR(150) DEFAULT NULL, point_repere VARCHAR(255) DEFAULT NULL, latitude NUMERIC(10, 7) DEFAULT NULL, longitude NUMERIC(10, 7) DEFAULT NULL, est_principale TINYINT DEFAULT 0 NOT NULL, utilisateur_id INT UNSIGNED NOT NULL, zone_livraison_id INT UNSIGNED DEFAULT NULL, INDEX IDX_C35F0816549469F6 (zone_livraison_id), INDEX idx_adresse_utilisateur (utilisateur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE avis (id INT UNSIGNED AUTO_INCREMENT NOT NULL, note SMALLINT UNSIGNED NOT NULL COMMENT \'1 a 5\', commentaire LONGTEXT DEFAULT NULL, statut_moderation VARCHAR(255) DEFAULT \'EN_ATTENTE\' NOT NULL, est_signale TINYINT DEFAULT 0 NOT NULL, motif_signalement VARCHAR(255) DEFAULT NULL, date_creation DATETIME NOT NULL, client_id INT UNSIGNED NOT NULL, produit_id INT UNSIGNED DEFAULT NULL, fournisseur_id INT UNSIGNED DEFAULT NULL, livreur_id INT UNSIGNED DEFAULT NULL, commande_id INT UNSIGNED NOT NULL, INDEX IDX_8F91ABF019EB6921 (client_id), INDEX IDX_8F91ABF0F347EFB (produit_id), INDEX IDX_8F91ABF0670C757F (fournisseur_id), INDEX IDX_8F91ABF0F8646701 (livreur_id), INDEX IDX_8F91ABF082EA2E54 (commande_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE categorie_produit (id INT UNSIGNED AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, categorie_parent_id INT UNSIGNED DEFAULT NULL, INDEX IDX_76264285DF25C577 (categorie_parent_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE client (points_fidelite INT UNSIGNED DEFAULT 0 NOT NULL, code_parrainage VARCHAR(20) NOT NULL, utilisateur_id INT UNSIGNED NOT NULL, parraine_par_id INT UNSIGNED DEFAULT NULL, UNIQUE INDEX UNIQ_C744045531F55253 (code_parrainage), INDEX IDX_C7440455405D4D4C (parraine_par_id), PRIMARY KEY (utilisateur_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE code_promo (id INT UNSIGNED AUTO_INCREMENT NOT NULL, code VARCHAR(30) NOT NULL, type_reduction VARCHAR(255) NOT NULL, valeur NUMERIC(10, 2) NOT NULL, date_debut DATETIME NOT NULL, date_fin DATETIME NOT NULL, utilisation_max INT UNSIGNED DEFAULT NULL, utilisation_actuelle INT UNSIGNED DEFAULT 0 NOT NULL, est_actif TINYINT DEFAULT 1 NOT NULL, UNIQUE INDEX UNIQ_5C4683B777153098 (code), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE commande (id INT UNSIGNED AUTO_INCREMENT NOT NULL, numero_commande VARCHAR(30) NOT NULL, statut VARCHAR(255) DEFAULT \'EN_ATTENTE\' NOT NULL, montant_produits NUMERIC(10, 2) DEFAULT \'0.00\' NOT NULL, frais_livraison NUMERIC(10, 2) DEFAULT \'0.00\' NOT NULL, montant_reduction NUMERIC(10, 2) DEFAULT \'0.00\' NOT NULL, montant_total NUMERIC(10, 2) DEFAULT \'0.00\' NOT NULL, creneau_livraison_debut DATETIME DEFAULT NULL, creneau_livraison_fin DATETIME DEFAULT NULL, date_commande DATETIME NOT NULL, date_maj DATETIME NOT NULL, client_id INT UNSIGNED NOT NULL, adresse_livraison_id INT UNSIGNED NOT NULL, zone_livraison_id INT UNSIGNED NOT NULL, code_promo_id INT UNSIGNED DEFAULT NULL, UNIQUE INDEX UNIQ_6EEAA67DCFFD611D (numero_commande), INDEX IDX_6EEAA67DBE2F0A35 (adresse_livraison_id), INDEX IDX_6EEAA67D549469F6 (zone_livraison_id), INDEX IDX_6EEAA67D294102D4 (code_promo_id), INDEX idx_commande_client (client_id), INDEX idx_commande_statut (statut), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE fournisseur (nom_commercial VARCHAR(150) NOT NULL, type_fournisseur VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, statut_validation VARCHAR(255) DEFAULT \'EN_ATTENTE\' NOT NULL, note_moyenne NUMERIC(2, 1) DEFAULT \'0.0\' NOT NULL, date_validation DATETIME DEFAULT NULL, utilisateur_id INT UNSIGNED NOT NULL, zone_livraison_id INT UNSIGNED DEFAULT NULL, INDEX IDX_369ECA32549469F6 (zone_livraison_id), INDEX idx_fournisseur_statut (statut_validation), PRIMARY KEY (utilisateur_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE ligne_commande (id INT UNSIGNED AUTO_INCREMENT NOT NULL, quantite NUMERIC(10, 2) NOT NULL, prix_unitaire NUMERIC(10, 2) NOT NULL, preparation_choisie VARCHAR(50) DEFAULT NULL, sous_total NUMERIC(10, 2) NOT NULL, commande_id INT UNSIGNED NOT NULL, produit_id INT UNSIGNED NOT NULL, fournisseur_id INT UNSIGNED NOT NULL, INDEX IDX_3170B74BF347EFB (produit_id), INDEX IDX_3170B74B670C757F (fournisseur_id), INDEX idx_lignecommande_commande (commande_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE litige (id INT UNSIGNED AUTO_INCREMENT NOT NULL, type_litige VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, statut VARCHAR(255) DEFAULT \'OUVERT\' NOT NULL, reponse LONGTEXT DEFAULT NULL, montant_rembourse NUMERIC(10, 2) DEFAULT NULL, date_ouverture DATETIME NOT NULL, date_resolution DATETIME DEFAULT NULL, commande_id INT UNSIGNED NOT NULL, client_id INT UNSIGNED NOT NULL, traite_par_id INT UNSIGNED DEFAULT NULL, INDEX IDX_EEE9D46D82EA2E54 (commande_id), INDEX IDX_EEE9D46D19EB6921 (client_id), INDEX IDX_EEE9D46D167FABE8 (traite_par_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE livraison (id INT UNSIGNED AUTO_INCREMENT NOT NULL, statut VARCHAR(255) DEFAULT \'EN_ATTENTE_AFFECTATION\' NOT NULL, date_affectation DATETIME DEFAULT NULL, date_prise_en_charge DATETIME DEFAULT NULL, date_livraison_effective DATETIME DEFAULT NULL, latitude_livraison NUMERIC(10, 7) DEFAULT NULL, longitude_livraison NUMERIC(10, 7) DEFAULT NULL, commentaire LONGTEXT DEFAULT NULL, commande_id INT UNSIGNED NOT NULL, livreur_id INT UNSIGNED DEFAULT NULL, UNIQUE INDEX UNIQ_A60C9F1F82EA2E54 (commande_id), INDEX idx_livraison_livreur (livreur_id), INDEX idx_livraison_statut (statut), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE livraison_position (id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL, latitude NUMERIC(10, 7) NOT NULL, longitude NUMERIC(10, 7) NOT NULL, horodatage DATETIME NOT NULL, livraison_id INT UNSIGNED NOT NULL, INDEX IDX_E5D012EF8E54FB25 (livraison_id), INDEX idx_position_livraison (livraison_id, horodatage), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE livreur (type_vehicule VARCHAR(255) NOT NULL, numero_permis VARCHAR(50) DEFAULT NULL, statut_disponibilite VARCHAR(255) DEFAULT \'HORS_LIGNE\' NOT NULL, latitude_actuelle NUMERIC(10, 7) DEFAULT NULL, longitude_actuelle NUMERIC(10, 7) DEFAULT NULL, note_moyenne NUMERIC(2, 1) DEFAULT \'0.0\' NOT NULL, utilisateur_id INT UNSIGNED NOT NULL, zone_affectation_id INT UNSIGNED DEFAULT NULL, INDEX IDX_EB7A4E6DD91E0456 (zone_affectation_id), INDEX idx_livreur_statut (statut_disponibilite), PRIMARY KEY (utilisateur_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE lot_peche (id INT UNSIGNED AUTO_INCREMENT NOT NULL, code_lot VARCHAR(50) NOT NULL COMMENT \'Encode dans le QR code\', lieu_peche VARCHAR(150) NOT NULL, date_heure_capture DATETIME NOT NULL, date_heure_mise_en_vente DATETIME NOT NULL, quantite_kg NUMERIC(10, 2) NOT NULL, indice_fraicheur NUMERIC(4, 1) DEFAULT \'100.0\' NOT NULL COMMENT \'Decroit automatiquement depuis la capture (0-100)\', est_retire TINYINT DEFAULT 0 NOT NULL, produit_id INT UNSIGNED NOT NULL, UNIQUE INDEX UNIQ_9F2D7CECE3A11EB1 (code_lot), INDEX idx_lot_produit (produit_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE notification (id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL, type VARCHAR(255) NOT NULL, titre VARCHAR(150) NOT NULL, contenu LONGTEXT NOT NULL, canal VARCHAR(255) NOT NULL, statut_lecture TINYINT DEFAULT 0 NOT NULL, date_envoi DATETIME NOT NULL, utilisateur_id INT UNSIGNED NOT NULL, INDEX IDX_BF5476CAFB88E14F (utilisateur_id), INDEX idx_notification_utilisateur (utilisateur_id, statut_lecture), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE paiement (id INT UNSIGNED AUTO_INCREMENT NOT NULL, mode_paiement VARCHAR(255) NOT NULL, montant NUMERIC(10, 2) NOT NULL, statut VARCHAR(255) DEFAULT \'EN_ATTENTE\' NOT NULL, reference_transaction VARCHAR(100) DEFAULT NULL, date_paiement DATETIME DEFAULT NULL, date_creation DATETIME NOT NULL, commande_id INT UNSIGNED NOT NULL, UNIQUE INDEX UNIQ_B1DC7A1E82EA2E54 (commande_id), INDEX idx_paiement_statut (statut), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE panier (id INT UNSIGNED AUTO_INCREMENT NOT NULL, date_creation DATETIME NOT NULL, client_id INT UNSIGNED NOT NULL, INDEX IDX_24CC0DF219EB6921 (client_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE panier_item (id INT UNSIGNED AUTO_INCREMENT NOT NULL, quantite NUMERIC(10, 2) NOT NULL, preparation_choisie VARCHAR(50) DEFAULT NULL, panier_id INT UNSIGNED NOT NULL, produit_id INT UNSIGNED NOT NULL, INDEX IDX_EBFD0067F77D927C (panier_id), INDEX IDX_EBFD0067F347EFB (produit_id), UNIQUE INDEX uq_panier_produit (panier_id, produit_id, preparation_choisie), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE produit (id INT UNSIGNED AUTO_INCREMENT NOT NULL, nom VARCHAR(150) NOT NULL, espece VARCHAR(150) NOT NULL COMMENT \'Ex: Thon, Tilapia, Crevette\', description LONGTEXT DEFAULT NULL, prix_unitaire NUMERIC(10, 2) NOT NULL, unite VARCHAR(255) DEFAULT \'KG\' NOT NULL, preparations_dispo VARCHAR(255) DEFAULT NULL COMMENT \'Ex: Entier,Filet,Decoupe\', stock_disponible NUMERIC(10, 2) DEFAULT \'0.00\' NOT NULL, photo_url VARCHAR(255) DEFAULT NULL, est_actif TINYINT DEFAULT 1 NOT NULL, date_creation DATETIME NOT NULL, date_maj DATETIME NOT NULL, fournisseur_id INT UNSIGNED NOT NULL, categorie_id INT UNSIGNED NOT NULL, INDEX idx_produit_fournisseur (fournisseur_id), INDEX idx_produit_categorie (categorie_id), INDEX idx_produit_actif (est_actif), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE utilisateur (id INT UNSIGNED AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, prenom VARCHAR(100) NOT NULL, email VARCHAR(180) NOT NULL, telephone VARCHAR(20) NOT NULL, mot_de_passe VARCHAR(255) NOT NULL, role VARCHAR(255) DEFAULT \'CLIENT\' NOT NULL, photo_profil VARCHAR(255) DEFAULT NULL, langue_preferee VARCHAR(255) DEFAULT \'FR\' NOT NULL, est_verifie TINYINT DEFAULT 0 NOT NULL, est_actif TINYINT DEFAULT 1 NOT NULL, date_creation DATETIME NOT NULL, date_maj DATETIME NOT NULL, code_otp VARCHAR(6) DEFAULT NULL, code_otp_expire_le DATETIME DEFAULT NULL, jeton_reinitialisation VARCHAR(100) DEFAULT NULL, jeton_expire_le DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_1D1C63B3E7927C74 (email), UNIQUE INDEX UNIQ_1D1C63B3450FF010 (telephone), INDEX idx_utilisateur_role (role), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE zone_livraison (id INT UNSIGNED AUTO_INCREMENT NOT NULL, nom_zone VARCHAR(100) NOT NULL, commune VARCHAR(100) NOT NULL, frais_livraison NUMERIC(10, 2) DEFAULT \'0.00\' NOT NULL, delai_estime_minutes INT UNSIGNED DEFAULT 45 NOT NULL, est_active TINYINT DEFAULT 1 NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE adresse ADD CONSTRAINT FK_C35F0816FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE adresse ADD CONSTRAINT FK_C35F0816549469F6 FOREIGN KEY (zone_livraison_id) REFERENCES zone_livraison (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE avis ADD CONSTRAINT FK_8F91ABF019EB6921 FOREIGN KEY (client_id) REFERENCES client (utilisateur_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE avis ADD CONSTRAINT FK_8F91ABF0F347EFB FOREIGN KEY (produit_id) REFERENCES produit (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE avis ADD CONSTRAINT FK_8F91ABF0670C757F FOREIGN KEY (fournisseur_id) REFERENCES fournisseur (utilisateur_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE avis ADD CONSTRAINT FK_8F91ABF0F8646701 FOREIGN KEY (livreur_id) REFERENCES livreur (utilisateur_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE avis ADD CONSTRAINT FK_8F91ABF082EA2E54 FOREIGN KEY (commande_id) REFERENCES commande (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE categorie_produit ADD CONSTRAINT FK_76264285DF25C577 FOREIGN KEY (categorie_parent_id) REFERENCES categorie_produit (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE client ADD CONSTRAINT FK_C7440455FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE client ADD CONSTRAINT FK_C7440455405D4D4C FOREIGN KEY (parraine_par_id) REFERENCES client (utilisateur_id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67D19EB6921 FOREIGN KEY (client_id) REFERENCES client (utilisateur_id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67DBE2F0A35 FOREIGN KEY (adresse_livraison_id) REFERENCES adresse (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67D549469F6 FOREIGN KEY (zone_livraison_id) REFERENCES zone_livraison (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67D294102D4 FOREIGN KEY (code_promo_id) REFERENCES code_promo (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE fournisseur ADD CONSTRAINT FK_369ECA32FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE fournisseur ADD CONSTRAINT FK_369ECA32549469F6 FOREIGN KEY (zone_livraison_id) REFERENCES zone_livraison (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE ligne_commande ADD CONSTRAINT FK_3170B74B82EA2E54 FOREIGN KEY (commande_id) REFERENCES commande (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ligne_commande ADD CONSTRAINT FK_3170B74BF347EFB FOREIGN KEY (produit_id) REFERENCES produit (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE ligne_commande ADD CONSTRAINT FK_3170B74B670C757F FOREIGN KEY (fournisseur_id) REFERENCES fournisseur (utilisateur_id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE litige ADD CONSTRAINT FK_EEE9D46D82EA2E54 FOREIGN KEY (commande_id) REFERENCES commande (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE litige ADD CONSTRAINT FK_EEE9D46D19EB6921 FOREIGN KEY (client_id) REFERENCES client (utilisateur_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE litige ADD CONSTRAINT FK_EEE9D46D167FABE8 FOREIGN KEY (traite_par_id) REFERENCES utilisateur (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE livraison ADD CONSTRAINT FK_A60C9F1F82EA2E54 FOREIGN KEY (commande_id) REFERENCES commande (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE livraison ADD CONSTRAINT FK_A60C9F1FF8646701 FOREIGN KEY (livreur_id) REFERENCES livreur (utilisateur_id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE livraison_position ADD CONSTRAINT FK_E5D012EF8E54FB25 FOREIGN KEY (livraison_id) REFERENCES livraison (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE livreur ADD CONSTRAINT FK_EB7A4E6DFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE livreur ADD CONSTRAINT FK_EB7A4E6DD91E0456 FOREIGN KEY (zone_affectation_id) REFERENCES zone_livraison (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE lot_peche ADD CONSTRAINT FK_9F2D7CECF347EFB FOREIGN KEY (produit_id) REFERENCES produit (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE paiement ADD CONSTRAINT FK_B1DC7A1E82EA2E54 FOREIGN KEY (commande_id) REFERENCES commande (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE panier ADD CONSTRAINT FK_24CC0DF219EB6921 FOREIGN KEY (client_id) REFERENCES client (utilisateur_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE panier_item ADD CONSTRAINT FK_EBFD0067F77D927C FOREIGN KEY (panier_id) REFERENCES panier (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE panier_item ADD CONSTRAINT FK_EBFD0067F347EFB FOREIGN KEY (produit_id) REFERENCES produit (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE produit ADD CONSTRAINT FK_29A5EC27670C757F FOREIGN KEY (fournisseur_id) REFERENCES fournisseur (utilisateur_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE produit ADD CONSTRAINT FK_29A5EC27BCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie_produit (id) ON DELETE RESTRICT');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE adresse DROP FOREIGN KEY FK_C35F0816FB88E14F');
        $this->addSql('ALTER TABLE adresse DROP FOREIGN KEY FK_C35F0816549469F6');
        $this->addSql('ALTER TABLE avis DROP FOREIGN KEY FK_8F91ABF019EB6921');
        $this->addSql('ALTER TABLE avis DROP FOREIGN KEY FK_8F91ABF0F347EFB');
        $this->addSql('ALTER TABLE avis DROP FOREIGN KEY FK_8F91ABF0670C757F');
        $this->addSql('ALTER TABLE avis DROP FOREIGN KEY FK_8F91ABF0F8646701');
        $this->addSql('ALTER TABLE avis DROP FOREIGN KEY FK_8F91ABF082EA2E54');
        $this->addSql('ALTER TABLE categorie_produit DROP FOREIGN KEY FK_76264285DF25C577');
        $this->addSql('ALTER TABLE client DROP FOREIGN KEY FK_C7440455FB88E14F');
        $this->addSql('ALTER TABLE client DROP FOREIGN KEY FK_C7440455405D4D4C');
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67D19EB6921');
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67DBE2F0A35');
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67D549469F6');
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67D294102D4');
        $this->addSql('ALTER TABLE fournisseur DROP FOREIGN KEY FK_369ECA32FB88E14F');
        $this->addSql('ALTER TABLE fournisseur DROP FOREIGN KEY FK_369ECA32549469F6');
        $this->addSql('ALTER TABLE ligne_commande DROP FOREIGN KEY FK_3170B74B82EA2E54');
        $this->addSql('ALTER TABLE ligne_commande DROP FOREIGN KEY FK_3170B74BF347EFB');
        $this->addSql('ALTER TABLE ligne_commande DROP FOREIGN KEY FK_3170B74B670C757F');
        $this->addSql('ALTER TABLE litige DROP FOREIGN KEY FK_EEE9D46D82EA2E54');
        $this->addSql('ALTER TABLE litige DROP FOREIGN KEY FK_EEE9D46D19EB6921');
        $this->addSql('ALTER TABLE litige DROP FOREIGN KEY FK_EEE9D46D167FABE8');
        $this->addSql('ALTER TABLE livraison DROP FOREIGN KEY FK_A60C9F1F82EA2E54');
        $this->addSql('ALTER TABLE livraison DROP FOREIGN KEY FK_A60C9F1FF8646701');
        $this->addSql('ALTER TABLE livraison_position DROP FOREIGN KEY FK_E5D012EF8E54FB25');
        $this->addSql('ALTER TABLE livreur DROP FOREIGN KEY FK_EB7A4E6DFB88E14F');
        $this->addSql('ALTER TABLE livreur DROP FOREIGN KEY FK_EB7A4E6DD91E0456');
        $this->addSql('ALTER TABLE lot_peche DROP FOREIGN KEY FK_9F2D7CECF347EFB');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAFB88E14F');
        $this->addSql('ALTER TABLE paiement DROP FOREIGN KEY FK_B1DC7A1E82EA2E54');
        $this->addSql('ALTER TABLE panier DROP FOREIGN KEY FK_24CC0DF219EB6921');
        $this->addSql('ALTER TABLE panier_item DROP FOREIGN KEY FK_EBFD0067F77D927C');
        $this->addSql('ALTER TABLE panier_item DROP FOREIGN KEY FK_EBFD0067F347EFB');
        $this->addSql('ALTER TABLE produit DROP FOREIGN KEY FK_29A5EC27670C757F');
        $this->addSql('ALTER TABLE produit DROP FOREIGN KEY FK_29A5EC27BCF5E72D');
        $this->addSql('DROP TABLE adresse');
        $this->addSql('DROP TABLE avis');
        $this->addSql('DROP TABLE categorie_produit');
        $this->addSql('DROP TABLE client');
        $this->addSql('DROP TABLE code_promo');
        $this->addSql('DROP TABLE commande');
        $this->addSql('DROP TABLE fournisseur');
        $this->addSql('DROP TABLE ligne_commande');
        $this->addSql('DROP TABLE litige');
        $this->addSql('DROP TABLE livraison');
        $this->addSql('DROP TABLE livraison_position');
        $this->addSql('DROP TABLE livreur');
        $this->addSql('DROP TABLE lot_peche');
        $this->addSql('DROP TABLE notification');
        $this->addSql('DROP TABLE paiement');
        $this->addSql('DROP TABLE panier');
        $this->addSql('DROP TABLE panier_item');
        $this->addSql('DROP TABLE produit');
        $this->addSql('DROP TABLE utilisateur');
        $this->addSql('DROP TABLE zone_livraison');
    }
}
