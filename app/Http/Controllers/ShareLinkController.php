<?php

namespace App\Http\Controllers;

use App\Models\ShareLink;
use Doctrine\DBAL\Query\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Vinkla\Hashids\Facades\Hashids;

class ShareLinkController extends Controller
{
    public function create_link_shop(Request $request){
        $validator = Validator::make($request->all(), [
            'link_shop' => 'required|url'
        ]);

        if($validator->fails()){
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try{
            $share_link_shop = ShareLink::where('link_article', null)->first();
            if($share_link_shop){
                return response()->json([
                    'success' => true,
                    'message' => 'Il existe déjà un lien boutique'
                ], 400);
            }
        
            $share_link = new ShareLink();
            $share_link->link_shop = $request->link_shop;
            $share_link->save();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => Hashids::encode($share_link->id),
                    'link_shop' => $share_link->link_shop
                ],
                'message' => 'Lien de la boutique ajouté avec succès'
            ]);
        }
        catch(QueryException $e){
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l’ajout du lien boutique',
                'erreur' => $e->getMessage()
            ], 500);
        }
    }

        public function create_link_article(Request $request){
        $validator = Validator::make($request->all(), [
            'link_article' => 'required|url'
        ]);

        if($validator->fails()){
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try{

            $share_link_article = ShareLink::where('link_shop', null)->first();
            if($share_link_article){
                return response()->json([
                    'success' => true,
                    'message' => 'Il existe déjà un lien article'
                ], 400);
            }

            $share_link = new ShareLink();
            $share_link->link_article = $request->link_article;
            $share_link->save();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => Hashids::encode($share_link->id),
                    'link_shop' => $share_link->link_article
                ],
                'message' => 'Lien de l’article ajouté avec succès'
            ]);
        }
        catch(QueryException $e){
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l’ajout du lien article',
                'erreur' => $e->getMessage()
            ], 500);
        }
    }

    public function get_links(){
        try{
            $share_link_shop = ShareLink::where('link_article', null)->first();
            $share_link_article = ShareLink::where('link_shop', null)->first();

            if(!$share_link_article && !$share_link_shop){
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'message' => 'Aucun lien ajouté'
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'link_shop' => [
                        'id' => Hashids::encode($share_link_shop->id),
                        'value' => $share_link_shop->link_shop
                    ] ?? null,
                    'link_article' => [
                        'id' => Hashids::encode($share_link_article->id),
                        'value' => $share_link_article->link_article
                    ] ?? null
                ],
                'message' => 'Liens affichés avec succès'
            ]);
        }
        catch(QueryException $e){
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l’affichage des liens',
                'erreur' => $e->getMessage()
            ], 500);
        }
    }

        public function update_link_shop(Request $request){
        $validator = Validator::make($request->all(), [
            'link_shop' => 'required|url'
        ]);

        if($validator->fails()){
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try{
            $share_link = ShareLink::where('link_article', null)->first();
            $share_link->link_shop = $request->link_shop ?? $share_link->link_shop;
            $share_link->save();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => Hashids::encode($share_link->id),
                    'link_shop' => $share_link->link_shop
                ],
                'message' => 'Lien de la boutique mis à jour avec succès'
            ]);
        }
        catch(QueryException $e){
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du lien boutique',
                'erreur' => $e->getMessage()
            ], 500);
        }
    }

        public function update_link_article(Request $request){
        $validator = Validator::make($request->all(), [
            'link_article' => 'required|url'
        ]);

        if($validator->fails()){
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try{
            $share_link = ShareLink::where('link_shop', null)->first();
            $share_link->link_article = $request->link_article ?? $share_link->link_article;
            $share_link->save();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => Hashids::encode($share_link->id),
                    'link_shop' => $share_link->link_article
                ],
                'message' => 'Lien de la boutique mis à jour avec succès'
            ]);
        }
        catch(QueryException $e){
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du lien article',
                'erreur' => $e->getMessage()
            ], 500);
        }
    }
}
