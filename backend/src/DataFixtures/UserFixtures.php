<?php

namespace App\DataFixtures;

use App\Entity\Course;
use App\Entity\Evaluation;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        // Create Admin
        $admin = new User();
        $admin->setUsername('admin1');
        $admin->setFirstName('Admin');
        $admin->setLastName('Administrator');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, '1nimda'));
        $manager->persist($admin);

        // Create Teachers
        $teacher1 = new User();
        $teacher1->setUsername('teacher1');
        $teacher1->setFirstName('Jan');
        $teacher1->setLastName('Janssen');
        $teacher1->setRoles(['ROLE_TEACHER']);
        $teacher1->setPassword($this->passwordHasher->hashPassword($teacher1, '1rehcaet'));
        $manager->persist($teacher1);

        $teacher2 = new User();
        $teacher2->setUsername('teacher2');
        $teacher2->setFirstName('Marie');
        $teacher2->setLastName('Peeters');
        $teacher2->setRoles(['ROLE_TEACHER']);
        $teacher2->setPassword($this->passwordHasher->hashPassword($teacher2, '2rehcaet'));
        $manager->persist($teacher2);

        // Create Students
        $students = [];

        $student1 = new User();
        $student1->setUsername('student1');
        $student1->setFirstName('Sofie');
        $student1->setLastName('Schuermans');
        $student1->setRoles(['ROLE_STUDENT']);
        $student1->setPassword($this->passwordHasher->hashPassword($student1, '1tneduts'));
        $manager->persist($student1);
        $students[] = $student1;

        $student2 = new User();
        $student2->setUsername('student2');
        $student2->setFirstName('Thomas');
        $student2->setLastName('De Vries');
        $student2->setRoles(['ROLE_STUDENT']);
        $student2->setPassword($this->passwordHasher->hashPassword($student2, '2tneduts'));
        $manager->persist($student2);
        $students[] = $student2;

        $student3 = new User();
        $student3->setUsername('student3');
        $student3->setFirstName('Lisa');
        $student3->setLastName('Vermeulen');
        $student3->setRoles(['ROLE_STUDENT']);
        $student3->setPassword($this->passwordHasher->hashPassword($student3, '3tneduts'));
        $manager->persist($student3);
        $students[] = $student3;

        $student4 = new User();
        $student4->setUsername('student4');
        $student4->setFirstName('Kevin');
        $student4->setLastName('Willems');
        $student4->setRoles(['ROLE_STUDENT']);
        $student4->setPassword($this->passwordHasher->hashPassword($student4, '4tneduts'));
        $manager->persist($student4);
        $students[] = $student4;

        // Create Courses
        $mathCourse = new Course();
        $mathCourse->setName('Wiskunde');
        $mathCourse->setDescription('Basis wiskunde voor eerste jaar');
        $mathCourse->setTeacher($teacher1);
        $manager->persist($mathCourse);

        $physicsCourse = new Course();
        $physicsCourse->setName('Fysica');
        $physicsCourse->setDescription('Inleiding tot de fysica');
        $physicsCourse->setTeacher($teacher1);
        $manager->persist($physicsCourse);

        $chemistryCourse = new Course();
        $chemistryCourse->setName('Chemie');
        $chemistryCourse->setDescription('Organische en anorganische chemie');
        $chemistryCourse->setTeacher($teacher2);
        $manager->persist($chemistryCourse);

        $biologyCourse = new Course();
        $biologyCourse->setName('Biologie');
        $biologyCourse->setDescription('Celbiologie en genetica');
        $biologyCourse->setTeacher($teacher2);
        $manager->persist($biologyCourse);

        $manager->flush();

        // Create Evaluations for Math Course (like in the example)
        // Sofie Schuermans evaluations
        $eval1 = new Evaluation();
        $eval1->setResult(10);
        $eval1->setWeight(4);
        $eval1->setMessage('Excellent work on test 1');
        $eval1->setStudent($student1);
        $eval1->setCourse($mathCourse);
        $manager->persist($eval1);

        $eval2 = new Evaluation();
        $eval2->setResult(0);
        $eval2->setWeight(2);
        $eval2->setMessage('Failed test 2');
        $eval2->setStudent($student1);
        $eval2->setCourse($mathCourse);
        $manager->persist($eval2);

        $eval3 = new Evaluation();
        $eval3->setResult(8);
        $eval3->setWeight(7);
        $eval3->setMessage('Good performance on final exam');
        $eval3->setStudent($student1);
        $eval3->setCourse($mathCourse);
        $manager->persist($eval3);

        // Thomas De Vries evaluations
        $eval4 = new Evaluation();
        $eval4->setResult(7);
        $eval4->setWeight(4);
        $eval4->setStudent($student2);
        $eval4->setCourse($mathCourse);
        $manager->persist($eval4);

        $eval5 = new Evaluation();
        $eval5->setResult(8);
        $eval5->setWeight(2);
        $eval5->setStudent($student2);
        $eval5->setCourse($mathCourse);
        $manager->persist($eval5);

        $eval6 = new Evaluation();
        $eval6->setResult(6);
        $eval6->setWeight(7);
        $eval6->setStudent($student2);
        $eval6->setCourse($mathCourse);
        $manager->persist($eval6);

        // Lisa Vermeulen evaluations
        $eval7 = new Evaluation();
        $eval7->setResult(9);
        $eval7->setWeight(4);
        $eval7->setStudent($student3);
        $eval7->setCourse($mathCourse);
        $manager->persist($eval7);

        $eval8 = new Evaluation();
        $eval8->setResult(-1); // Did not participate
        $eval8->setWeight(2);
        $eval8->setMessage('Absent during test');
        $eval8->setStudent($student3);
        $eval8->setCourse($mathCourse);
        $manager->persist($eval8);

        $eval9 = new Evaluation();
        $eval9->setResult(9);
        $eval9->setWeight(7);
        $eval9->setStudent($student3);
        $eval9->setCourse($mathCourse);
        $manager->persist($eval9);

        // Physics Course evaluations
        $eval10 = new Evaluation();
        $eval10->setResult(8);
        $eval10->setWeight(5);
        $eval10->setStudent($student1);
        $eval10->setCourse($physicsCourse);
        $manager->persist($eval10);

        $eval11 = new Evaluation();
        $eval11->setResult(7);
        $eval11->setWeight(3);
        $eval11->setStudent($student2);
        $eval11->setCourse($physicsCourse);
        $manager->persist($eval11);

        // Chemistry Course evaluations
        $eval12 = new Evaluation();
        $eval12->setResult(6);
        $eval12->setWeight(4);
        $eval12->setStudent($student3);
        $eval12->setCourse($chemistryCourse);
        $manager->persist($eval12);

        $eval13 = new Evaluation();
        $eval13->setResult(8);
        $eval13->setWeight(6);
        $eval13->setStudent($student4);
        $eval13->setCourse($chemistryCourse);
        $manager->persist($eval13);

        // Biology Course evaluations
        $eval14 = new Evaluation();
        $eval14->setResult(9);
        $eval14->setWeight(5);
        $eval14->setStudent($student1);
        $eval14->setCourse($biologyCourse);
        $manager->persist($eval14);

        $eval15 = new Evaluation();
        $eval15->setResult(7);
        $eval15->setWeight(4);
        $eval15->setStudent($student4);
        $eval15->setCourse($biologyCourse);
        $manager->persist($eval15);

        $manager->flush();
    }
}
