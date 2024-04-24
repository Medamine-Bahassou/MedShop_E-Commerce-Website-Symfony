<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\User;
use App\Form\ProfileType;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;

class HomeController extends AbstractController
{

    private $productRepository ;
    private $categoryRepository ;
    private $entityManager ; 
    public function __construct(
        UserRepository $userRepository,
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
        ManagerRegistry $doctrine
        )
    {
        $this->productRepository = $productRepository ; 
        $this->categoryRepository = $categoryRepository ;

        $this->entityManager = $doctrine->getManager() ; 
    }


    #[Route('/', name: 'home')]
    public function index(): Response
    {
        $products = $this->productRepository->findAll();
        $categories = $this->categoryRepository->findAll();
        return $this->render('home/index.html.twig', [
            'products' => $products,
            'categories' => $categories,
        ]);
    }




    #[Route('/products', name: 'products')]
    public function products(): Response
    {
        $products = $this->productRepository->findAll();
        $categories = $this->categoryRepository->findAll();
        return $this->render('product/products.html.twig', [
            'products' => $products,
            'categories' => $categories,
        ]);
    }

    #[Route('/product/{categoryid}', name: 'product_category')]
    public function categoryProducts($categoryid): Response
    {
        $category = new Category();
        $category = $this->categoryRepository->find($categoryid);
        $categories = $this->categoryRepository->findAll();

        return $this->render('product/products.html.twig', [
            'products' => $category->getProducts(),
            'categories' => $categories,
        ]);
    }




}
