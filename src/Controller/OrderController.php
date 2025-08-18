<?php

namespace App\Controller;

use App\Entity\City;
use App\Entity\Order;
use App\Service\Cart;
use App\Form\OrderType;
use App\Entity\OrderProducts;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class OrderController extends AbstractController
{
    #[Route('/order', name: 'app_order')]
    public function index(EntityManagerInterface $entityManager, ProductRepository $productRepository, 
                            SessionInterface $session, Request $request, Cart $cart): Response
    {
       
        $data = $cart->getCart($session);

        $order= new Order();
        $form= $this->createForm(OrderType::class, $order);
        $form-> handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            if($order->isPayOnDelivery()) {
                if(!empty($data['total'])) { 

                    $order->setTotalPrice(($data['total']));
                    $order->setCreatedAt(new \DatetimeImmutable());
                    $entityManager->persist($order);
                    $entityManager->flush();

                    foreach($data['cart'] as $value) { // pour chaque elements dans le panier
                        $orderProduct = new OrderProducts(); 
                        $orderProduct->setOrder($order); 
                        $orderProduct->setProduct($value['product']); 
                        $orderProduct->setQuantity($value['quantity']);
                        $entityManager->persist($orderProduct);
                        $entityManager->flush();
                    }
                }

                $session->set('cart', []);

                return $this->redirectToRoute('order_message');
                
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

    #[Route('/editor/order', name: 'app_orders_show')] 
    public function getAllOrder(OrderRepository $orderRepo,PaginatorInterface $paginator, Request $request): Response
    {
        $orders= $orderRepo->findAll();
        $orderPagination = $paginator->paginate(
            $orders,
            $request->query->getInt('page',1),
            2
        );

        return $this->render('order/orders.html.twig', [
            'orders'=>$orderPagination,
        ]);
    }

     #[Route('/editor/order/{id}/remove', name: 'app_orders_remove')]
    public function removeOrder(Order $order, EntityManagerInterface $entityManager):Response 
    {
        $entityManager->remove($order);
        $entityManager->flush();
        $this->addFlash('danger', 'Commande supprimée');
        return $this->redirectToRoute('app_orders_show',['type']);
    }

}
