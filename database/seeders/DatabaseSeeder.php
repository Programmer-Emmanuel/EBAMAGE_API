<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Prix;
use App\Models\Seuil;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Log::info('Début du seeding de la base de données');

        // -------------------------
        // 1. Créer un utilisateur
        // -------------------------
        $userId = DB::table('users')->insertGetId([
            'nom_clt' => 'User Test',
            'email_clt' => 'marcbamidele@gmail.com',
            'tel_clt' => '0140022693',
            'password_clt' => Hash::make('user1234'),
            'is_verify' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        

        Log::info('Utilisateur créé avec ID: ' . $userId);

        // -------------------------
        // 2. Créer une boutique
        // -------------------------
        $boutiqueId = DB::table('boutiques')->insertGetId([
            'nom_btq' => 'Boutique Test',
            'email_btq' => 'boutique@test.com',
            'tel_btq' => '0987654321',
            'image_btq' => 'https://tse1.mm.bing.net/th/id/OIP.m5CGd0j8oWDHkEssXqJSsgHaHa?cb=12ucfimg=1&rs=1&pid=ImgDetMain&o=7&rm=3',
            'password_btq' => Hash::make('boutique1234'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Log::info('Boutique créée avec ID: ' . $boutiqueId);

        // -------------------------
        // 3. Créer une catégorie
        // -------------------------
        $categorieId = DB::table('categories')->insertGetId([
            'nom_categorie' => 'Mode Homme',
            'image_categorie' => 'https://images.unsplash.com/photo-1617137968427-85924c800a22?w=400&h=400&fit=crop',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Log::info('Catégorie créée avec ID: ' . $categorieId);

        // -------------------------
        // 4. Créer une variation
        // -------------------------
        $variationId = DB::table('variations')->insertGetId([
            'nom_variation' => 'taille',
            'lib_variation' => json_encode(['S', 'M', 'L', 'XL']),
            'id_btq' => $boutiqueId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Log::info('Variation créée avec ID: ' . $variationId);

        // -------------------------
        // 5. Créer un article
        // -------------------------
        $articleId = DB::table('articles')->insertGetId([
            'nom_article' => 'Chemise Slim Fit',
            'prix' => 15000,
            'old_price' => 18000,
            'images' => json_encode([
                'https://images.unsplash.com/photo-1598033129183-c4f50c736f10?w=400&h=400&fit=crop',
                'https://images.unsplash.com/photo-1602810318383-e386cc2a3ccf?w=400&h=400&fit=crop'
            ]),
            'description' => 'Chemise slim fit élégante pour homme en coton de haute qualité.',
            'id_btq' => $boutiqueId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Log::info('Article créé avec ID: ' . $articleId);

        // -------------------------
        // 6. Lier l'article à la catégorie
        // -------------------------
        DB::table('corresponds')->insert([
            'id_article' => $articleId,
            'id_categorie' => $categorieId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Log::info('Liaison article-catégorie créée');

        // -------------------------
        // 7. Lier l'article à la variation
        // -------------------------
        DB::table('article_variations')->insert([
            'id_article' => $articleId,
            'id_variation' => $variationId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Log::info('Liaison article-variation créée');

        // -------------------------
        // 8. Créer des villes et communes
        // -------------------------
        $villes = [
            'Abidjan' => ['Cocody', 'Marcory', 'Yopougon', 'Treichville', 'Abobo'],
            'Yamoussoukro' => ['Attiégouakro', 'Kossou'],
            'Bouaké' => ['Béoumi', 'Sakassou'],
        ];

        $villeCount = 0;
        $communeCount = 0;

        foreach ($villes as $villeName => $communes) {
            $villeId = DB::table('villes')->insertGetId([
                'lib_ville' => $villeName,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $villeCount++;

            foreach ($communes as $communeName) {
                DB::table('communes')->insert([
                    'lib_commune' => $communeName,
                    'id_ville' => $villeId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $communeCount++;
            }
        }

        Log::info("Géographie créée: {$villeCount} villes et {$communeCount} communes");

        $admin = new Admin();
        $admin->nom = "Administrateur EBAMAGE";
        $admin->email = "administrateur@gmail.com";
        $admin->tel = "0140022693";
        $admin->password = Hash::make("ebamage_admin#12345");
        $admin->role = "super_admin";
        $admin->save();

        Log::info("Administrateur créé.");

        $prix = new Prix();
        $prix->prix = 1000;
        $prix->save();

        $seuil = new Seuil();
        $seuil->seuil = 50000;
        $seuil->save();

        // -------------------------
        // 9. Affichage console
        // -------------------------
        $this->command->info('✅ Seeding terminé avec succès!');
        $this->command->info("   - 1 utilisateur créé");
        $this->command->info("   - 1 boutique créée");
        $this->command->info("   - 1 catégorie créée");
        $this->command->info("   - 1 variation créée");
        $this->command->info("   - 1 article créé");
        $this->command->info("   - {$villeCount} villes créées");
        $this->command->info("   - {$communeCount} communes créées");
        $this->command->info("   - Administrateur créé!");
        $this->command->info("   - Prix de livraison créé!");
        $this->command->info("   - Seuil de prix livraison gratuite créé!");
    }
}
