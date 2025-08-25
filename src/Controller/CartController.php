<?php

namespace App\Controller;

use App\Entity\Product;
use App\Service\Cart;
use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

final class CartController extends AbstractController
{
    public function __construct(private readonly ProductRepository $productRepository) //private=accessible que depuis l'interieur de la classe, readonly=propriete assignée une seule fois dans le constructeur
    {
        
    }

    #[Route('/cart', name: 'app_cart', methods:['GET'])]
    public function index(SessionInterface $session, Cart $cart): Response
    {  $data= $cart->getCart($session);
        
        return $this->render('cart/cart.html.twig', [
            'items'=>$data['cart'],
            'total'=>$data['total'],
        ]);
        
    }

    #[Route('/cart/add/{id}/', name: 'app_cart_new', methods:['GET'])]
    //Définit une route pour ajouter un produit au panier
    public function addProductToCart(int $id, SessionInterface $session, Request $request, Product $product): Response //int veut dire qu'on attend obligatoirement que l'id soit un entier
    //Méthode pour ajouter un produit au panier, prend l'ID du produit et la session en paramètres
    {
        $cart = $session->get('cart',[]);
        $stock = $product->getStock();
        // Récupère le panier actuel de la session, ou un tableau vide si il n'existe pas
        if (!empty($cart[$id])){
            $cart[$id]++;
        }else{
            $cart[$id]=1;
        }
          // Si le produit est déjà dans le panier, incrémente sa quantité sinon l'ajoute avec une quantité de 1
        
        if ($cart[$id] > $stock){
            $this->addFlash('danger', 'Le stock est insuffisant pour le moment, maximum ' .$stock. ' produits');
        
            return $this->redirect($request->headers->get('referer'));
        } 


      
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
