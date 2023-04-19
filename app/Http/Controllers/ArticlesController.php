<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Download;
use Illuminate\Http\Request;
use Imagick;
use App\Mail\DownloadLink;
use Illuminate\Support\Arr;

class ArticlesController extends Controller
{
    const ITEM_PER_PAGE = 24;
    public function uploadFile($request)
    {
        $name = 'article_' . time() . "." . $request->file('file_link')->guessClientExtension();
        $link = $request->file('file_link')->storeAs('articles', $name, 'public');

        return $link;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $searchParams = $request->all();
        $articleQuery = Article::query();
        $limit = Arr::get($searchParams, 'limit', static::ITEM_PER_PAGE);
        $keyword = Arr::get($searchParams, 'keyword', '');
        if (!empty($keyword)) {
            $articleQuery->where(function ($q) use ($keyword) {
                $q->where('title', 'LIKE', '%' . $keyword . '%');
                $q->orWhere('author', 'LIKE', '%' . $keyword . '%');
                $q->orWhere('publisher', 'LIKE', '%' . $keyword . '%');
                $q->orWhere('article_source', 'LIKE', '%' . $keyword . '%');
                $q->orWhere('created_at', 'LIKE', '%' . $keyword . '%');
            });
        }
        $articles = $articleQuery->where('is_published', 1)->orderBy('id', 'DESC')->paginate($request->limit);
        return response()->json(compact('articles'), 200);
    }
    public function allArticles(Request $request)
    {
        $user = $this->getUser();
        if ($user->isAdmin()) {

            $articles = Article::with('approver')->orderBy('id', 'DESC')->paginate($request->limit);
        } else {
            $articles = Article::where('uploaded_by', $user->id)->orderBy('id', 'DESC')->paginate($request->limit);
        }

        return response()->json(compact('articles'), 200);
    }

    public function myArticles(Request $request)
    {
        $user = $this->getUser();
        $articles = Article::where('uploaded_by', $user->id)->orderBy('id', 'DESC')->paginate($request->limit);

        // foreach ($articles as $article) {
        //     $mediaItems = $article->getMedia();
        //     $article->original_image_url = $mediaItems[0]->getUrl();  // the url to the where the original image is stored
        //     $article->thumbnail_url = $mediaItems[0]->getUrl('thumb');
        // }
        return response()->json(compact('articles'), 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required',
            'author' => 'required',
            'publisher' => 'required',
            'uploaded_file' => 'required|mimes:jpg,png,pdf,doc,docx,mp4,xls,xlsx,csv,ppt,pptx',
        ]);
        $user = $this->getUser();
        $article = new Article();
        $article->title = $request->title;
        $article->type = $request->type;
        $article->author = $request->author;
        $article->publisher = $request->publisher;
        $article->description = $request->description;
        $article->article_source = $request->article_source;
        $article->extension = $request->file('uploaded_file')->guessClientExtension(); //$this->uploadFile($request);
        $article->uploaded_by = $user->id;
        $article->save();
        $media = $article->addMediaFromRequest('uploaded_file')->toMediaCollection();
        $article->file_link = $media->getUrl();
        $article->save();
        return 'success';
        // $original_image_path = $media->getPath();  // the path to the where the original image is stored
        // $thumbnail_path = $media->getPath('thumb'); // the path to the converted image with dimensions 368x232

        // $original_image_url = $media->getUrl();  // the url to the where the original image is stored
        // $thumbnail_url = $media->getUrl('thumb'); // the url to the converted image with dimensions 368x232


        // return response()->json(compact('original_image_path', 'thumbnail_path', 'original_image_url', 'thumbnail_url'), 200); //$this->read($article);
        // $path = portalPulicPath($article->file_link);

        // // return $path;
        // $imagick = new Imagick($path);
        // // $imagick->readImage($path[0]);
        // $imagick->thumbnailImage(0, 0);
        // echo $imagick;
    }
    public function read(Article $article)
    {
        // $folder = 'schools/1561062061/materials/material_1586028505.docx';
        $path = portalPulicPath($article->file_link);
        $article
            ->addMedia($path)
            ->toMediaCollection();
        return response()->json(compact('article'), 200);
        // return response()->file($path);
    }
    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Request  $article
     * @return \Illuminate\Http\Response
     */
    public function saveDownloadSurvey(Request $request)
    {
        $download = new Download();
        $download->article_id = $request->article_id;
        $download->full_name = $request->full_name;
        $download->email = $request->email;
        $download->reason = ($request->reason !== 'Others') ? $request->reason : $request->other_reason;
        $download->link = $request->link;
        $download->save();

        \Mail::to($download)->send(new DownloadLink($download));

        return 'success';
    }

    public function downloads(Request $request)
    {
        $downloads = Download::with('article')->orderBy('id', 'DESC')->paginate($request->limit);
        return response()->json(compact('downloads'), 200);
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Article  $article
     * @return \Illuminate\Http\Response
     */
    public function approve(Request $request, Article $article)
    {
        //
        $article->approved_by = $this->getUser()->id;
        $article->save();
        return response()->json([], 204);
    }

    public function publish(Request $request, Article $article)
    {
        //
        $article->is_published = !$article->is_published;
        $article->save();
        return response()->json([], 204);
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Article  $article
     * @return \Illuminate\Http\Response
     */
    public function destroy(Article $article)
    {
        //
        $article->delete();
        return response()->json([], 204);
    }

    public function downloadFile($folder, $file)
    {
        return view('application');
        // $file = portalPulicPath('media/' . $folder . '/' . $file);

        // // $headers = array(
        // //     'Content-Type: application/pdf',
        // // );

        // return response()->download($file);
        // return response()->download('storage/media/' . $folder . '/' . $file);
    }
}
