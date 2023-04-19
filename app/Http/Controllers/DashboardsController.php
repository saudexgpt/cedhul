<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Article;
use App\Models\Download;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardsController extends Controller
{
    /**
     * This manages privileges based on roles
     *
     * @return \Illuminate\Http\Response
     */

    public function accessDenied()
    {
        return $this->render('errors.403');
    }

    public function navbarNotificationClicked()
    {
        session(['navbar_clicked' => 'true']);
    }

    public function navbarNotification()
    {
        $user = $this->getUser();
        $today = Carbon::now();
        $school_id = $this->getSchool()->id;
        $sess_id = $this->getSession()->id;
        $term_id = $this->getTerm()->id;

        $user_activities = $user->activityLog()->where('created_at', '>', $today->startOfWeek())->orderBy('id', 'DESC')->get();

        $activities = collect($user_activities);
        if ($user->hasRole('teacher')) {
            $staff = $this->getStaff();

            $class_activities = [];
            $class_teachers =  ClassTeacher::where(['school_id' => $school_id, 'teacher_id' => $staff->id])->get();
            foreach ($class_teachers as $class_teacher) {
                $class_activities = $class_teacher->classActivity()->orderBy('id', "DESC")->get();
                $activities = $activities->merge($class_activities);
            }
        }
        if ($user->hasRole('parent')) {
            # code...
            $guardian  = $this->getGuardian();
            $ward_ids = $guardian->ward_ids;

            $ward_id_array = explode('~', substr($ward_ids, 1));

            $class_teacher_id_array = [];


            foreach ($ward_id_array as $key => $student_id) :
                //make sure the student_id does not have an empty value
                if ($student_id != "") {

                    $student_in_class_obj = new StudentsInClass();
                    $student_class = $student_in_class_obj->fetchStudentInClass($student_id, $sess_id, $term_id, $school_id);

                    $class_teacher_id = $student_class->class_teacher_id;

                    //make class_teacher_id_array unique to avoid duplicate activity display from siblings of a parent in the same class
                    if (!in_array($class_teacher_id, $class_teacher_id_array)) {
                        //get the recent class activity for the week
                        $class_activities = ClassActivity::where(['class_teacher_id' => $class_teacher_id, 'school_id' => $school_id])->where('created_at', '>', $today->startOfWeek())->orderBy('id', 'DESC')->get();

                        $activities = $activities->merge($class_activities);
                    }
                    $class_teacher_id_array[] = $class_teacher_id;
                }

            endforeach;
        }
        $activities = $activities->sortByDesc('created_at');

        $notifications = News::where('school_id', $school_id)
            ->where('targeted_audience', 'like', $user->role)
            ->orWhere('targeted_audience', 'like', $user->role . '~%')
            ->orWhere('targeted_audience', 'like', '%~' . $user->role . '~%')
            ->orWhere('targeted_audience', 'like', '%~' . $user->role)
            ->orderBy('id', 'DESC')->get();

        if (!$notifications->isEmpty()) {
            foreach ($notifications as $notification) {

                $seen_by_array  = explode('~', $notification->seen_by);

                $notification->seen_by_array = $seen_by_array;
            }
        }
        $count_value = 0;
        if (session()->exists('count_notification')) {
            $count_value = session('count_notification');
        }
        $count = 0;

        foreach ($activities as $activity) {
            if ($activity->actor_id != $user->id) {
                $count++;
            }
        }
        if ($count > $count_value) {
            session(['count_notification' => ($count - $count_value)]);
            session()->forget(['navbar_clicked']);
            $count_value = session('count_notification');
        }

        if (session()->exists('navbar_clicked')) {
            $count_value = "";
        }

        return view('news.navbar_notification', compact('activities', 'user', 'notifications', 'count_value'));
    }
    public function welcome()
    {
        return $this->render('layouts.home');
    }
    public function index()
    {
        //Flash::success('You are welcome back');
        //$user = new User();
        $user = $this->getUser();

        if ($user->hasRole('super') || $user->hasRole('admin')) {
            $published_article = Article::where('is_published', 1)->count();
            $unapproved_article = Article::where('approved_by', NULL)->count();
            $downloads = Download::count();
            $users = User::count();
            return response()->json(compact('published_article', 'unapproved_article', 'downloads', 'users'));
        }
        $approved_article = Article::where('approved_by', '!=', NULL)->where('uploaded_by', $user->id)->count();
        $unapproved_article = Article::where('approved_by', NULL)->where('uploaded_by', $user->id)->count();
        return response()->json(compact('approved_article', 'unapproved_article'));
    }
}
