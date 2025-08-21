<?php

namespace App\Controller;

use App\Entity\City;
use App\Entity\Order;
use App\Service\Cart;
use App\Form\OrderType;
use App\Entity\OrderProducts;
use App\Service\StripePayment;
use Symfony\Component\Mime\Email;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

// final class OrderController extends AbstractController
// {
class OrderController extends AbstractController
{
    public function __construct(private MailerInterface $mailer){
    }
    
    #[Route('/order', name: 'app_order')]
    public function index(EntityManagerInterface $entityManager, ProductRepository $productRepository, 
                            SessionInterface $session, Request $request, Cart $cart): Response
    {
       // Récupère les données du panier à partir de laa session 
        $data = $cart->getCart($session);
        //créer un nouvel objet order
        $order= new Order();
        //créé un formulaire pour gérer la création de la commande
        $form= $this->createForm(OrderType::class, $order);
        //gère la soumission du formulaire
        $form-> handleRequest($request);
        //quand c'est true 
        if ($form->isSubmitted() && $form->isValid()) {  
                // Vérifie si le total du panier n'est pas vide
                if(!empty($data['total'])) {
                    $totalPrice = $data['total'] + $order->getCity()->getShippingCost();
                    // Définit le prix total de la commande
                    $order->setTotalPrice($totalPrice);
                    // Définit la date de création de la commande
                    $order->setCreatedAt(new \DateTimeImmutable());
                    $order->setIsPaymentCompleted(0); //on initialise a false 
                    //dd($order);
                    $entityManager->persist($order);
                    $entityManager->flush();
                    // Boucle sur chaque élément du panier
                    foreach($data['cart'] as $value) {
                        // Crée un nouvel objet OrderProducts
                        $orderProduct = new OrderProducts();
                        // Définit la commande pour le produit de la commande
                        $orderProduct->setOrder($order);
                        // Définit le produit pour le produit de la commande
                        $orderProduct->setProduct($value['product']);
                        // Définit la quantité pour le produit de la commande
                        $orderProduct->setQuantity($value['quantity']);
                        // Enregistre le produit de la commande dans la base de données
                        $entityManager->persist($orderProduct);
                        $entityManager->flush();
                    }

                    if($order->isPayOnDelivery()) {
                        // Mise à jour du contenu du panier en session
                        $session->set('cart', []);

                        $html = $this->renderView('mail/orderConfirm.html.twig',[ //crée une vue mail
                            'order'=>$order //on recupere le $order apres le flush donc on a toutes les infos
                            
                        ]);
                        $email = (new Email()) //On importe la classe depuis Symfony\Component\Mime\Email;
                        ->from('mortalkombat@gmailcom') //Adresse de l'expéditeur donc notre boutique ou vous mêmes
                        //->to('to@gmailcom') //Adresse du receveur
                        ->to($order->getEmail())
                        ->subject('Confirmation de réception de commande') //Intitulé du mail
                        ->html($html);
                        $this->mailer->send($email);
        
                        // Redirection vers la page du panier
                        return $this->redirectToRoute('order_message');
                    }
                    // quand c'est false
                    $paymentStripe = new StripePayment(); //on importe notre service avec sa classe
                    $shippingCost = $order->getCity()->getShippingCost();
                    $paymentStripe->startPayment($data, $shippingCost, $order->getId()); //on importe le panier donc $data
                    $stripeRedirectUrl = $paymentStripe->getStripeRedirectUrl();
                    //dd( $stripeRedirectUrl);
                    return $this->redirect($stripeRedirectUrl);
                }
            }
            
            return $this->render('order/index.html.twig', [
                'form'=>$form->createView(),
                'total'=>$data['total'],
            ]);

        }

    #[Route('/city/{id}/shipping/cost', name: 'app_city_shipping_cost')]
    public function cityShippingCost(City $city): Response
    {
        $cityShippingPrice = $city->getShippingCost();

        return new Response(json_encode(['status'=>200, "message"=>'on','content'=>$cityShippingPrice]));

    }

    #[Route('/editor/order/{id}/is-completed/update', name: 'app_orders_is-completed-update')]
    public function isCompletedUpdate(Request $request, $id, OrderRepository $orderRepository, EntityManagerInterface $entityManager):Response
    {
        $order = $orderRepository->find($id);
        $order->setIsCompleted(true);
        $entityManager->flush();
        $this->addFlash('success', 'Modification effectuée');
        return $this->redirect($request->headers->get('referer'));//cela fait reference a la route precedent cette route ci
    }

    #[Route('/order_message', name: 'order_message')] 
    public function orderMessage(): Response
    {
        return $this->render('order/order_message.html.twig');
    }

    #[Route('/editor/order/{type}', name: 'app_orders_show')] 
    public function getAllOrder($type,OrderRepository $orderRepo,PaginatorInterface $paginator, Request $request): Response
    {
        // $orders= $orderRepo->findAll();

        if($type == 'is-completed'){
            $data = $orderRepo->findBy(['isCompleted'=>1],['id'=>'DESC']);
        }else if($type == 'pay-on-stripe-not-delivered'){
            $data = $orderRepo->findBy(['isCompleted'=>null,'payOnDelivery'=>0,'isPaymentCompleted'=>1],['id'=>'DESC']);
        }else if($type == 'pay-on-stripe-is-delivered'){
            $data = $orderRepo->findBy(['isCompleted'=>1,'payOnDelivery'=>0,'isPaymentCompleted'=>1],['id'=>'DESC']);
        }else if($type == 'no_delivery'){
            $data = $orderRepo->findBy(['isCompleted'=>null,'payOnDelivery'=>0,'isPaymentCompleted'=>0],['id'=>'DESC']);
        }else if($type == 'all'){
            $data = $orderRepo->findAll();
        }

        $orders= $paginator->paginate(
            $data,
            $request->query->getInt('page',1),
            2
        );

        return $this->render('order/orders.html.twig', [
            'orders'=>$orders,
        ]);
    }

     #[Route('/editor/order/{id}/remove', name: 'app_orders_remove')]
    public function removeOrder(Order $order,Request $request, EntityManagerInterface $entityManager):Response 
    {
        $entityManager->remove($order);
        $entityManager->flush();
        $this->addFlash('danger', 'Commande supprimée');
        return $this->redirect($request->headers->get('referer'));
    }

}
