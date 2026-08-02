<?php
// Verifies buildCeldiReportCard()'s arithmetic against the two real 2025-2026
// cards extracted from the school's PDF (Angel D. Doe, Tianna D. Tokpawhiea).
require_once dirname(__DIR__) . '/core/Controller.php';

$sem = new ReflectionMethod('Controller', 'celdiSemesterAverage');
$sem->setAccessible(true);
$rnd = new ReflectionMethod('Controller', 'celdiRound');
$rnd->setAccessible(true);

// subject => [p1,p2,p3,e1, expected s1, p4,p5,p6,e2, expected s2, expected yearly]
$cards = [
'Angel D. Doe' => [
 'English'            => [74,93,86,86, 85, 84,80,91,98, 92, 89],
 'Mathematics'        => [69,75,79,79, 77, 91,84,89,72, 80, 79],
 'RME/Bible'          => [82,87,95,90, 89, 98,99,90,90, 93, 91],
 'General Science'    => [70,88,73,95, 86, 67,90,75,88, 83, 85],
 'History'            => [81,64,95,85, 83, 96,86,81,95, 91, 87],
 'Literature'         => [77,81,91,92, 88, 90,90,90,99, 95, 92],
 'Phonics'            => [83,96,89,97, 93, 89,81,78,78, 80, 87],
 'Spelling/Vocabulary'=> [90,87,98,95, 93, 88,98,90,96, 94, 94],
 'Geography'          => [64,73,83,89, 81, 82,75,78,78, 78, 80],
 'Civics'             => [75,81,79,74, 76, 87,76,93,78, 82, 79],
 'French'             => [92,92,80,64, 76, 92,92,80,72, 80, 78],
 'P. E./Recreation'   => [89,89,90,89, 89, 89,85,85,85, 86, 88],
 'Computer'           => [96,96,98,98, 97, 97,90,91,91, 92, 95],
],
'Tianna D. Tokpawhiea' => [
 'English'            => [60,78,90,81, 79, 72,69,84,65, 70, 75],
 'Mathematics'        => [71,77,80,80, 78, 83,69,70,61, 68, 73],
 'RME/Bible'          => [75,87,95,90, 88, 92,85,91,90, 90, 89],
 'General Science'    => [60,74,66,88, 77, 65,69,78,73, 72, 75],
 'History'            => [77,60,95,78, 78, 90,76,78,73, 77, 78],
 'Literature'         => [80,73,85,86, 83, 75,67,60,72, 70, 77],
 'Phonics'            => [82,88,85,90, 88, 89,75,85,85, 84, 86],
 'Spelling/Vocabulary'=> [89,87,95,90, 90, 93,88,90,90, 90, 90],
 'Geography'          => [81,73,73,83, 79, 74,84,77,84, 81, 80],
 'Civics'             => [65,76,89,63, 70, 71,67,63,69, 68, 69],
 'French'             => [95,91,75,60, 74, 89,83,85,74, 80, 77],
 'P. E./Recreation'   => [89,89,90,89, 89, 89,80,89,89, 88, 89],
 'Computer'           => [94,97,94,94, 95, 95,84,83,83, 85, 90],
],
];

// Average row as printed on each card, in column order p1,p2,p3,e1,s1,p4,p5,p6,e2,s2,yr
$expectedAverages = [
 'Angel D. Doe'         => [80.0,85.0,87.0,87.0,85.6, 88.0,87.0,85.0,86.0,86.6, 86.5],
 'Tianna D. Tokpawhiea' => [78.0,81.0,86.0,82.0,82.2, 83.0,77.0,79.0,78.0,78.7, 80.6],
];

$pass = 0; $fail = 0;
foreach ($cards as $student => $subjects) {
    $cols = ['p1'=>[],'p2'=>[],'p3'=>[],'e1'=>[],'s1'=>[],'p4'=>[],'p5'=>[],'p6'=>[],'e2'=>[],'s2'=>[],'yr'=>[]];
    foreach ($subjects as $name => $d) {
        [$p1,$p2,$p3,$e1,$expS1,$p4,$p5,$p6,$e2,$expS2,$expYr] = $d;

        $s1 = $sem->invoke(null, [$p1,$p2,$p3], (float)$e1);
        $s2 = $sem->invoke(null, [$p4,$p5,$p6], (float)$e2);
        $yr = $rnd->invoke(null, ($s1 + $s2) / 2, 0);

        foreach ([['Sem1',$s1,$expS1], ['Sem2',$s2,$expS2], ['Yearly',$yr,$expYr]] as [$lbl,$got,$want]) {
            if ((float)$got === (float)$want) { $pass++; }
            else { $fail++; printf("  MISMATCH %s / %-20s %s: got %s want %s\n", $student, $name, $lbl, $got, $want); }
        }
        foreach (['p1'=>$p1,'p2'=>$p2,'p3'=>$p3,'e1'=>$e1,'s1'=>$s1,'p4'=>$p4,'p5'=>$p5,'p6'=>$p6,'e2'=>$e2,'s2'=>$s2,'yr'=>$yr] as $k=>$v) {
            $cols[$k][] = $v;
        }
    }
    // Average row: recorded columns print as whole numbers, derived ones to 1dp.
    $derived = ['s1' => true, 's2' => true, 'yr' => true];
    $i = 0;
    foreach ($cols as $k => $vals) {
        $avg = $rnd->invoke(null, array_sum($vals) / count($vals), isset($derived[$k]) ? 1 : 0);
        $want = $expectedAverages[$student][$i++];
        if ((float)$avg === (float)$want) { $pass++; }
        else { $fail++; printf("  MISMATCH %s / Average row %s: got %s want %s\n", $student, $k, $avg, $want); }
    }
}

// Grading scale banding
foreach ([[95,'E'],[90,'E'],[89,'S'],[85,'S'],[84,'I'],[80,'I'],[79,'N'],[72,'N'],[71,'C'],[60,'C'],[0,'C']] as [$score,$want]) {
    $got = Controller::celdiLetter((float)$score);
    if ($got === $want) { $pass++; } else { $fail++; printf("  MISMATCH scale %d: got %s want %s\n", $score, $got, $want); }
}

printf("\n%d passed, %d failed\n", $pass, $fail);
exit($fail === 0 ? 0 : 1);
