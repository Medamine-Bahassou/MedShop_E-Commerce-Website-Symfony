<?php

namespace App\Controller;

use App\Entity\Historique;
use App\Entity\Order;
use App\Entity\Product;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\HistoriqueRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use PhpParser\Node\Name;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\BrowserKit\History;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Role\Role;
use tidy;

class OrderController extends AbstractController
{

    private $historiqueRepository ;
    private $orderRepository ;
    private $entityManager ; 
    private $productRepository ; 
    public function __construct(
        HistoriqueRepository $historiqueRepository,
        OrderRepository $orderRepository,
        ManagerRegistry $doctrine,
        ProductRepository $productRepository  
        )
    {
        $this->historiqueRepository = $historiqueRepository ; 
        $this->orderRepository = $orderRepository ; 
        $this->productRepository = $productRepository ; 
        $this->entityManager = $doctrine->getManager() ; 
    }

    #[Route('/orders', name: 'orders_list')]
    public function index(): Response
    {

        if(!in_array("ROLE_ADMIN", $this->getUser()->getRoles())){
            return $this->redirectToRoute('home');
        }

        $orders = $this->orderRepository->findAll();
        return $this->render('order/index.html.twig', [
            'orders' =>  $orders ,
        ]);
    }

    

    #[Route('/user/orders', name: 'user_order_list')]
    public function userOrders(): Response
    {

        if(!$this->getUser())
        {
            return $this->redirectToRoute('app_login');
        }
        
        $historique = $this->historiqueRepository->findBy(['userid' => $this->getUser()]);

        return $this->render('order/user.html.twig', [
            'user' => $this->getUser(),
            'historiques' =>  $historique ,
        ]);
    }     


    #[Route('/store/order/{productid}', name: 'order_store')]
    public function store($productid): Response
    {
        if(!$this->getUser())
        {
            return $this->redirectToRoute('app_login');
        }

        if(in_array("ROLE_ADMIN", $this->getUser()->getRoles())){
            return $this->redirectToRoute('home');
        }

        $order = new Order();
        $product = new Product();
        $product = $this->productRepository->find($productid);
        

        $orderExist = $this->orderRepository->findOneBy([
            'user' => $this->getUser(),
            'pname' => $product->getName(),
            'productid' => $product->getId(),
        ]);

        if($orderExist)
        {
            if(isset($_POST['quantity']) )
            {
                if($product->getQuantity() >= $_POST['quantity'])
                {

                    $count = $_POST['quantity'];

                    // $count = $orderExist->getQuantity();
                    // $count++ ;  
                    if($count >= 1){
                        $orderExist->setQuantity($count);
                    }
                    else{
                        $this->addFlash(
                            'warning',
                            'Enter a quantity'
                        );    
                        return $this->redirectToRoute('product_show',[
                            'product' => $product,
                            'id' => $productid ,

                        ]);
                        // return $this->render('product/show.html.twig',[
                        //     'product' => $product,
                        //     'id' => $productid ,
                        // ]);            
    
                    }
                    

                    $this->entityManager->persist($orderExist);
                    $this->entityManager->flush();
                }
                else
                {
                    $this->addFlash(
                        'warning',
                        'You have exceeded the allowed number of products'
                    );
                    return $this->render('product/show.html.twig',[
                        'product' => $product,
                    ]);            
                }
            }

            return $this->redirectToRoute('panier');            

        }
        else{
            if(isset($_POST['quantity']) ){
                $order->setQuantity($_POST['quantity']);
            }
            else{
                $order->setQuantity(1);
            }
            $order->setPname($product->getName());
            $order->setPrice($product->getPrice());
            $order->setStatus('Not Payed');
            $order->setUser($this->getUser());
            $order->setProductid($product->getId());

            $this->entityManager->persist($order);
            $this->entityManager->flush();
            $this->addFlash(
                'success',
                'Your product was saved'
            );
            return $this->redirectToRoute('panier');    
        }
                
    }

    #[Route('/update/order/{orderid}/{status}', name: 'order_status_update')]
    public function updateOrderStatus($orderid, $status): Response
    {

        $order = new Order(); 
        $historique= new Historique();

        $order = $this->orderRepository->find($orderid);
        $order->setStatus($status) ; 
        $historique->setUserid($order->getUser()->getId());
        $historique->setStatus($status);
        $historique->setProduit($order->getPname());
        $date = new DateTimeImmutable();
        $historique->setDateOrder($date);
        $historique->setQuantity($order->getQuantity());
        $historique->setPrixTotale($order->getPrice());

        $this->entityManager->persist($historique);
        if($order->getStatus() == 'Shipped' || $order->getStatus() == 'Rejected'){
            $this->entityManager->remove($order);
        }
        $this->entityManager->flush();
        
        $this->addFlash(
            'success',
            'Order status was Updated'
        );

        return $this->redirectToRoute('panier');            
    }

    #[Route('/update/order/{orderid}', name: 'order_delete')]
    public function deleteOrder($orderid): Response
    {

        $order = new Order(); 

        $order = $this->orderRepository->find($orderid);

        $this->entityManager->remove($order);
        $this->entityManager->flush();
        $this->addFlash(
            'success',
            'Order status was Deleted'
        );
        
        if(in_array("ROLE_ADMIN", $this->getUser()->getRoles()))
            return $this->redirectToRoute('orders_list');  
        else
            return $this->redirectToRoute('panier');  


    }

    #[Route('/panier', name: 'panier')]
    public function panier(): Response
    {
        $orders = new Order();
        $historique = new Historique();

        if(in_array("ROLE_ADMIN", $this->getUser()->getRoles())){
            return $this->redirectToRoute('home');
        }
        $order = $this->orderRepository->findAll();
        $historique = $this->historiqueRepository->findBy(['userid' => $this->getUser()]);
        return $this->render('order/panier.html.twig', [
            'historiques' =>  $historique ,
            'orders' =>  $order ,
        ]);
    }


    #[Route('/payer', name: 'payer')]
    public function payer(): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            throw $this->createNotFoundException('User not found.');
        }
        
        $orders = $this->orderRepository->findBy(['user' => $user]);
        $historique= new Historique();
        $status = 'In Progress' ; 
        foreach ($orders as $order) {
            $order->setStatus($status) ; 
            $historique->setUserid($order->getUser()->getId());
            $historique->setStatus($status);
            $historique->setProduit($order->getPname());
            $date = new DateTimeImmutable();
            $historique->setDateOrder($date);
            $historique->setQuantity($order->getQuantity());
            $historique->setPrixTotale($order->getPrice());
    
            $this->entityManager->persist($historique);
            if($order->getStatus() == 'Shipped' || $order->getStatus() == 'Rejected'){
                $this->entityManager->remove($order);
            }
            
            
            $this->entityManager->persist($order);
        }

        $this->addFlash(
            'success',
            'Order status was Updated'
        );       
        
        $this->entityManager->flush();
        
        return $this->redirectToRoute('panier');
    }
     
    

    



}
