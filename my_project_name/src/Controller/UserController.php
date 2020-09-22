<?php

namespace App\Controller;

use App\Entity\Users;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Validator;


class UserController extends AbstractController
{

    private $passwordEncoder;
    protected $validator;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder, ValidatorInterface $validator)
    {
        $this->passwordEncoder = $passwordEncoder;
        $this->validator = $validator;
    }

    /**
     * @Route("/users", name="users", methods={"GET"} )
     * @param UsersRepository $usersRepository
     * @return JsonResponse
     */
    public function getUsers(UsersRepository $usersRepository): JsonResponse
    {
        $data = $usersRepository->findAll();

        return $this->json([
            'status' => 'success',
            'data' => $data
        ], 200);
    }

    /**
     * @Route("/users/{id}", name="getUserById", methods={"GET"}  )
     * @param UsersRepository $usersRepository
     * @param $id
     * @return JsonResponse
     */
    public function getUserById(UsersRepository $usersRepository, $id): JsonResponse
    {
        $data = $usersRepository->find($id);

        return $this->json([
            'status' => 'success',
            'data' => $data
        ], 200);
    }

    /**
     * @Route("/users", name="addUser", methods={"POST"} )
     * @param UsersRepository $usersRepository
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return JsonResponse
     */
    public function addUser(UsersRepository $usersRepository, Request $request, EntityManagerInterface $entityManager)
    {

        $input = $this->transformRequest($request);

        $errorList = $this->validateEmails($input['email']);

        if (count($errorList) > 0) {
            return $this->json([
                'status' => 'error',
                'data' => $errorList
            ]);
        }

        $user = new Users();

        $user->setName($input['name']);
        $user->setEmail($input['email']);
        $user->setPassword($this->passwordEncoder->encodePassword($user, $input['password']));

        $entityManager->persist($user);
        $entityManager->flush();

        return $this->json([
            'success' => 'User added successfully',
        ], 200);

    }

    /**
     * @Route("/users/{id}", name="deleteUser", methods={"DELETE"} )
     * @param UsersRepository $usersRepository
     * @param $id
     * @param EntityManagerInterface $entityManager
     * @return JsonResponse
     */
    public function deleteUser(UsersRepository $usersRepository, $id, EntityManagerInterface $entityManager)
    {

        $user = $usersRepository->find($id);

        if (!$user) {

            return $this->json([
                'fail' => 'User not fond',
            ], 404);
        }

        $entityManager->remove($user);
        $entityManager->flush();

        return $this->json([
            'success' => 'User delete',
        ], 200);
    }

    /**
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param UsersRepository $usersRepository
     * @param $id
     * @return JsonResponse
     * @Route("/users/{id}", name="update-user", methods={"PATCH"})
     */
    public function updateUser(Request $request, EntityManagerInterface $entityManager, UsersRepository $usersRepository, $id): JsonResponse
    {

        $user = $usersRepository->find($id);

        if (!$user) {

            return $this->json([
                'fail' => 'User not fond',
            ], 404);
        }

        $input = $this->transformRequest($request);

        $errorList = $this->validateEmails($input['email']);

        if (count($errorList) > 0) {
            return $this->json([
                'status' => 'error',
                'data' => $errorList
            ]);
        }

        $user->setName($input['name']);
        $user->setEmail($input['email']);
        $user->setPassword($this->passwordEncoder->encodePassword($user, $input['password']));

        $entityManager->persist($user);
        $entityManager->flush();

        return $this->json([
            'success' => 'User updated successfully',
        ], 200);

    }


    /**
     * Convert request data to array
     * @param Request $request
     * @return array|mixed
     */
    public function transformRequest(Request $request)
    {
        $parametersAsArray = [];
        if ($content = $request->getContent()) {
            $parametersAsArray = json_decode($content, true);
        }
        return $parametersAsArray;
    }

    /**
     * Validate email function
     * @param $emails
     * @return array
     */
    public function validateEmails($emails): array
    {

        $errors = array();
        $emails = is_array($emails) ? $emails : array($emails);

        $validator = $this->validator;

        $constraints = array(
            new \Symfony\Component\Validator\Constraints\Email(),
            new \Symfony\Component\Validator\Constraints\NotBlank()
        );

        foreach ($emails as $email) {

            $error = $validator->validate($email, $constraints);

            if (count($error) > 0) {
                $errors[] = $error;
            }
        }

        return $errors;
    }


}
