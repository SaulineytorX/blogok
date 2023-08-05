<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\Category;
use App\Models\Tag;


use Illuminate\Support\Facades\Storage;
use App\Http\Requests\PostRequest;
use Illuminate\Support\Facades\Cache;

class PostController extends Controller
{

    public function __construct()
    {
        $this->middleware('can:admin.posts.index')->only('index');
        $this->middleware('can:admin.posts.create')->only('create', 'store');
        $this->middleware('can:admin.posts.edit')->only('edit', 'update');
        $this->middleware('can:admin.posts.destroy')->only('destroy');
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $posts = post::all();
        return view('admin.posts.index', compact('posts'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = Category::pluck('name', 'id');
        $tags = Tag::all();

        return view('admin.posts.create', compact('categories', 'tags'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PostRequest $request)
    {
        $post = Post::create($request->all());

        if ($request->file('file')) {
            $url = $request->file('file')->store('public/posts');
            $post->image()->create([
                'url' => $url
            ]);
        }


            if ($request->hasFile('image')) {
                $post->addMediaFromRequest('image')->toMediaCollection('images');
            }



        Cache::flush();

        if ($request->tags) {
            $post->tags()->attach($request->tags);
        }

        return redirect()->route('admin.posts.edit', $post)->with('info', 'El Post se creó con éxito');
    }



    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Post $post)
    {
        $this->authorize('author', $post);

        $categories = Category::pluck('name', 'id');
        $tags = Tag::all();
        return view('admin.posts.edit', compact('post', 'categories', 'tags'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PostRequest $request, Post $post)
{
    $this->authorize('author', $post);
    $post->update($request->all());

    if ($request->file('file')) {
        $url = $request->file('file')->store('public/posts');
        if ($post->image) {
            Storage::delete($post->image->url);

            $post->image()->update([
                'url' => $url
            ]);
        } else {
            $post->image()->create([
                'url' => $url
            ]);
        }
    }

        if ($request->hasFile('image')) {
            $post->clearMediaCollection('images');
            $post->addMediaFromRequest('image')->toMediaCollection('images');
        }



    Cache::flush();

    return redirect()->route('admin.posts.edit', $post)->with('info', 'El Post se actualizó con éxito');
}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Post $post)
    {
        $this->authorize('author', $post);
        $post->delete();


        Cache::flush();

        return redirect()->route('admin.posts.index')->with('info', 'La etiqueta se eliminó con éxito');
    }



}
