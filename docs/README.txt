AutoGrade 5 Usage Documentation
--------------------------------
This project is a collection of scripts that implements an automatic grader system. The system can be used to grade programming assignments written in a variety of languages. It also includes scripts to interact with Canvas (or another LMS).

The script takes care of a lot of the boring and time-consuming grunt-work associated with grading programs (downloading submissions, organizing student work, compiling, running tests, producing reports, uploading student feedback). It gives you a good sketch of a programs’ correctness leaving only a brief manual review to be done.

The normal workflow is:

1. Download student submissions from Canvas to a grading folder. The grader may use either the manager.html page to control the process or download the submissions using Canvas.

2. Run a prep script to make folders for every student and place their work in their folder. If the work is in a zip or 7z file, the work will be extracted from the archive file.

3. Run a test script to grade the assignment. Either specific students may be graded or all students. The script can compile, run, and examine a source code file or the output of a computer program. It creates a grade.log file of the results.

The grading script is customized for every assignment and makes use of various functions of the Grader class, TestCase classes and Evaluator classes. The TestCase and Evaluator classes are plugin modules to support grading actions. The Grader class keeps track of points added or subtracted to the score by the TestCase classes and the Evaluator classes summarize and finalize scores for each section of an assignment.

4. The optional Reviewer class and script lets the human grader review all or some submissions.

5. The optional Analyzer class and script provides summary statistics for each student and the entire class.

6. When finished grading, upload the grade.log file of every student to Canvas using manager.html, or manually by copy-pasting into Canvas.

In addition, there are various utility scripts for:
-Creating a CSV file of folder names created by Canvas and the student name. This is useful to address the students by name in the grading report.
-Creating a web page of student photos with student names. This is useful for learning student names.

AutoGrade 5 Usage Documentation
--------------------------------
See the following files for more information:
-install.txt: Installing the autograde5 system
-grading.txt: Tutorial on writing grading scripts
-design.txt: Design overview and notes
