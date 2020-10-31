<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Translation\Exception\NotFoundResourceException;
use Symfony\Component\Validator\Exception\ValidatorException;

/**
 * @Route("/api/users")
 */
class UserApiController extends AbstractController
{
    /**
     * @Route("/", name="users_get", methods={"GET"})
     */
    public function getUsers(UserRepository $userRepository): JsonResponse
    {
        $users = $userRepository->findAll();
        return new JsonResponse($users);
    }

    /**
     * @Route("/", name="users_add", methods={"POST"})
     */
    public function addUser(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordEncoderInterface $passwordEncoder
    ): JsonResponse
    {
        try {
            $user = new User();
            $form = $this->createForm(RegistrationFormType::class, $user, ['csrf_protection' => false]);
            $this->processForm($request, $form);
            if ($form->isSubmitted() && !$form->isValid()) {
                throw new ValidatorException('There was a validation error');
            }
            $user->setPassword(
                $passwordEncoder->encodePassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );
            $em->persist($user);
            $em->flush();
        } catch (ValidatorException $e) {
            $errorsFromForm = $this->getErrorsFromForm($form);
            return new JsonResponse([
                'error' => $e->getMessage(),
                'notValidField' => $errorsFromForm
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => "An error occurred while creating the user"], 500);
        }
        return $this->createJsonResponse($user, "User added successfully");
    }

    /**
     * @param Request $request
     * @param FormInterface $form
     */
    private function processForm(Request $request, FormInterface $form): void
    {
        $data = json_decode($request->getContent(), true);
        $form->submit($data);
    }

    private function getErrorsFromForm(FormInterface $form)
    {
        $errors = array();
        foreach ($form->getErrors() as $error) {
            $errors[] = $error->getMessage();
        }
        foreach ($form->all() as $childForm) {
            if ($childForm instanceof FormInterface) {
                if ($childErrors = $this->getErrorsFromForm($childForm)) {
                    $errors[$childForm->getName()] = $childErrors;
                }
            }
        }
        return $errors;
    }

    /**
     * @param User $user
     * @return JsonResponse
     */
    private function createJsonResponse(User $user, string $message): JsonResponse
    {
        $jsonResponse = new JsonResponse([
            'success' => $message,
        ], 201);
        $jsonResponse->headers->set(
            'Location',
            $this->generateUrl("user_get", ["id" => $user->getId()])
        );
        return $jsonResponse;
    }

    /**
     * @Route("/{id}", name="user_get", methods={"GET"})
     */
    public function getUserData(int $id, UserRepository $userRepository): JsonResponse
    {
        $user = $userRepository->find($id);
        if (!$user) {
            return new JsonResponse([
                'erorr' => sprintf(
                    'No user found with id %s',
                    $id
                )
            ], 404);
        }
        return new JsonResponse($user);
    }

    /**
     * @Route("/{id}", name="user_update", methods={"PUT"})
     */
    public function updateUser(
        Request $request,
        int $id,
        EntityManagerInterface $em,
        UserRepository $userRepository,
        UserPasswordEncoderInterface $passwordEncoder
    ): JsonResponse
    {
        try {
            $user = $userRepository->find($id);
            if (!$user) {
                throw new NotFoundResourceException(
                    sprintf('No user found with id %s', $id),
                    404
                );
            }
            $form = $this->createForm(RegistrationFormType::class, $user, ['csrf_protection' => false]);
            $this->processForm($request, $form);
            if ($form->isSubmitted() && !$form->isValid()) {
                throw new ValidatorException('There was a validation error');
            }
            $user->setPassword(
                $passwordEncoder->encodePassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );
            $em->persist($user);
            $em->flush();
        } catch (ValidatorException $e) {
            $errorsFromForm = $this->getErrorsFromForm($form);
            return new JsonResponse([
                'error' => $e->getMessage(),
                'notValidField' => $errorsFromForm
            ]);
        } catch (\Exception $e) {
            $code = ($e->getCode()) ?: 500;
            return new JsonResponse([
                'erorr' => $e->getMessage()
            ], $code);
        }

        return $this->createJsonResponse($user, "User data modify successfully.");;
    }

    /** @Route("/{id}", name="user_delete", methods={"DELETE"}) */
    public function deleteUser(
        int $id,
        UserRepository $userRepository,
        EntityManagerInterface $em
    )
    {
        try {
            $user = $userRepository->find($id);
            if (!$user) {
                throw new NotFoundResourceException(
                    sprintf('No user found with id %s', $id),
                    404
                );
            }
            $em->remove($user);
            $em->flush();
        } catch (\Exception $e) {
            $code = ($e->getCode()) ?: 500;
            return new JsonResponse([
                'erorr' => $e->getMessage()
            ], $code);
        }
        return new JsonResponse(["success" => "The user has been deleted."]);
    }
}
