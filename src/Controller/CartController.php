<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

final class CartController extends AbstractController
{
    public function __construct(private readonly ProductRepository $productRepository) //private=accessible que depuis l'interieur de la classe, readonly=propriete assignée une seule fois dans le constructeur
    {
        
    }

    #[Route('/cart', name: 'app_cart', methods:['GET'])]
    public function index(SessionInterface $session): Response
    {   //récupère les données du panier
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
        return $this->render('cart/cart.html.twig', [
            'controller_name' => 'CartController',
            'items' =>$cartWithData,//on retourne ses deux variables afin de les récuperer dans la vue
            'total' =>$total
        ]);
    }

    #[Route('/cart/add/{id}/', name: 'app_cart_new', methods:['GET'])]
    //Définit une route pour ajouter un produit au panier
    public function addProductToCart(int $id, SessionInterface $session): Response //int veut dire qu'on attend obligatoirement que l'id soit un entier
    //Méthode pour ajouter un produit au panier, prend l'ID du produit et la session en paramètres
    {
        $cart= $session->get('cart',[]);
        // Récupère le panier actuel de la session, ou un tableau vide si il n'existe pas
        if (!empty($cart[$id])){
            $cart[$id]++;
        }else{
            $cart[$id]=1;
        }
        // Si le produit est déjà dans le panier, incrémente sa quantité sinon l'ajoute avec une quantité de 1
        $session->set('cart',$cart);
        //Met à jour le panier dans la session 
        return $this->redirectToRoute('app_cart');
        // Redirige vers la page du panier
    }

    #[Route('/cart/remove/{id}', name: 'app_cart_product_remove', methods:['GET'])]
    
    public function removeToCart($id, SessionInterface $session): Response 
    {
        $cart= $session->get('cart',[]);
        
        if (!empty($cart[$id])){
            unset($cart[$id]);

        }
        
        $session->set('cart',$cart);
        
        return $this->redirectToRoute('app_cart');
       
    }

    #[Route('/cart/remove', name: 'app_cart_remove', methods:['GET'])]
    
    public function removeCart(SessionInterface $session): Response 
    {
        
        $session->set('cart', []);
        
        return $this->redirectToRoute('app_cart');
       
    }
}
