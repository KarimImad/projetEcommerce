<?php

namespace App\Service;

use App\Repository\ProductRepository;


class Cart{

    public function __construct (private readonly ProductRepository $productRepository){
    }
    
        public function getCart ($session):array{

      
            $cart = $session->get('cart',[]);
            //initialisation d'un tableau pour stocker les données du panier avec les informations de produits
            $cartWithData=[];
            //Boucle sur les élements du panier pour récupérer les informations de produit
            foreach ($cart as $id => $quantity) {

                //Récupère le produit correspondant à l'Id et la quantité
                $cartWithData[] = [
                    'product' => $this->productRepository->find($id),
                    'quantity' => $quantity
                ];
            }
            //calcul total du panier
            $total = array_sum(array_map(function ($item){
                //Pour chaque élément du panier, multiplie le prix du produit par la quantité
                return $item['product']->getPrice() * $item['quantity'];

            }, $cartWithData));

            // dd($cartWithData);

            //Rendu de la vue pour afficher le panier
            return [
                'cart' =>$cartWithData,//on retourne ses deux variables afin de les récuperer dans la vue
                'total' =>$total
            ];
        }

}