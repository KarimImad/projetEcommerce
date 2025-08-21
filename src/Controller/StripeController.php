<?php

namespace App\Controller;

use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Stripe;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

final class StripeController extends AbstractController
{
    #[Route('/pay/success', name: 'app_stripe_success')]
    public function success(SessionInterface $session): Response
    {
        $session->set('cart',[]);
        return $this->render('stripe/success.html.twig', [
            'controller_name' => 'StripeController',
        ]);
    }

     #[Route('/pay/cancel', name: 'app_stripe_cancelled')]
    public function cancel(): Response
    {
        return $this->render('stripe/cancelled.html.twig', [
            'controller_name' => 'StripeController',
        ]);
    }

     #[Route('/stripe/notify', name: 'app_stripe_notify')]
    public function stripeNotify(Request $request, OrderRepository $orderRepository, EntityManagerInterface $entityManager):Response
    {
        // file_put_contents("log.txt", "test");
        
        Stripe::setApiKey($_SERVER['STRIPE_SECRET_KEY']);
        
        // Définir la clé de webhook de Stripe
        $endpoint_secret = $_SERVER['STRIPE_WEBHOOK_SECRET'];
        // Récupérer le contenu de la requête
        $payload = $request->getContent();
        
        // Récupérer l'en-tête de signature de la requête
        $sigHeader = $request->headers->get('Stripe-Signature');
        // Initialiser l'événement à null
        $event = null;

        try {
            // file_put_contents("log.txt","try", FILE_APPEND);
            
            // Construire l'événement à partir de la requête et de la signature
            $event = \Stripe\Webhook::constructEvent(
                $payload, $sigHeader, $endpoint_secret
            );
            // file_put_contents("log.txt","ok", FILE_APPEND);
        } catch (\UnexpectedValueException $e) {
            // Retourner une erreur 400 si le payload est invalide
            return new Response('Invalid payload', 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            // Retourner une erreur 400 si la signature est invalide
            return new Response('Invalid signature', 400);
        }

        // Gérer les différents types d'événements
        switch ($event->type) {
            case 'payment_intent.succeeded':  // Événement de paiement réussi
                // Récupérer l'objet payment_intent
                $paymentIntent = $event->data->object;

              // Enregistrer les détails du paiement dans un fichier
                // $fileName = 'stripe-detail-'.uniqid().'.txt';
                $orderId = $paymentIntent->metadata->orderid;
                $order = $orderRepository->find($orderId);
                $cartPrice = $order->getTotalPrice();
                $stripeTotalAmount = $paymentIntent->amount/100;
                if($cartPrice==$stripeTotalAmount){
                $order->setIsPaymentCompleted(1);
                $entityManager->flush();
                }
                // file_put_contents("order.txt", $orderId);
                break;
            case 'payment_method.attached':  //évenement de méthode de paiement attachée
                // Récupérer l'objet payment_method
                $paymentMethod = $event->data->object;
                break;
            default :
                // Ne rien faire pour les autres types d'évènements
                break;
            }

            //Retourner une réponse 200 pour indiquer que l'évènement a été reçu avec succès
            return new Response('Evenement reçu avec succès', 200);
            

    }

}
