<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Models\Todo;
use App\Models\Category;
use App\Listeners\LogTaskActivity;

class TodoController extends Controller
{
    public function index()
    {
        $title = 'My Tasks';
        $subtitle = 'Daftar Tasks';

        $todos = Todo::where('user_id', Auth::id())
        ->with('category')

        // Search Filter
        ->when(request('status'), fn($q) => $q->where('status', request('status')))

        ->when(request('category'), fn($q) => $q->where('category_id', request('category')))

        ->when(request('search'), fn($q) => $q->where(function($q) {
            $q->where('title', 'like', '%' . request('search') . '%')
            ->orWhere('description', 'like', '%' . request('search') . '%');
        }))

        ->latest()
        ->get();

        $category = Category::where('user_id', Auth::id())->get();

        return view('todo.index', compact('title', 'subtitle', 'todos', 'category'));
    }
    public function show($id)
    {
        $title = 'My Tasks';
        $subtitle = 'Detail Tasks';

        $todo = Todo::where('user_id', Auth::id())
            ->where('id', $id)
            ->with('category')
            ->firstOrFail();
            
        return view('todo.show', compact('title', 'subtitle', 'todo'));
    }
    public function search(Request $request)
    {
        $query = $request->get('q', '');

        if (strlen($query) < 1) {
            return response()->json([]);
        }

        $todos = Todo::where('user_id', Auth::id())
            ->where(function($q) use ($query) {
                $q->where('title', 'like', '%' . $query . '%')
                ->orWhere('description', 'like', '%' . $query . '%');
            })
            ->with('category')
            ->latest()
            ->take(6)
            ->get()
            ->map(fn($todo) => [
                'id'       => $todo->id,
                'title'    => $todo->title,
                'status'   => $todo->status,
                'priority' => $todo->priority,
                'category' => $todo->category?->name,
                'color'    => $todo->category?->color ?? '#94a3b8',
                'url'      => route('todo.edit', $todo->id),
            ]);

        return response()->json($todos);
    }

    public function create()
    {
        $title = 'My Tasks';
        $subtitle = 'Tambah Tasks';

        $category = Category::where('user_id', Auth::id())->get();

        return view('todo.create', compact('title', 'subtitle', 'category'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:category,id',
            'priority'    => 'required|in:low,medium,high',
            'deadline'    => 'nullable|date|after_or_equal:today',
            'file'        => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg|max:5120',
        ], [
            'title.required'          => 'Judul task wajib diisi.',
            'priority.required'       => 'Priority wajib dipilih.',
            'priority.in'             => 'Priority tidak valid.',
            'deadline.date'           => 'Format deadline tidak valid.',
            'deadline.after_or_equal' => 'Deadline tidak boleh sebelum hari ini.',
        ]);

        $filepath = null;
        $filename = null;

        if ($request->hasFile('file')) {
            $file     = $request->file('file');
            $filename = $file->getClientOriginalName();
            $filepath = $file->store('todo-files', 'public');
        }

        Todo::create([
            'user_id'     => Auth::id(),
            'category_id' => $request->category_id,
            'title'       => $request->title,
            'description' => $request->description,
            'priority'    => $request->priority,
            'status'      => 'pending',
            'deadline'    => $request->deadline,
            'file_path'   => $filepath,
            'file_name'   => $filename,
        ]);

        // Log activity
        (new LogTaskActivity)->logCreate(Auth::id());

        return redirect()->route('todo.index')->with('success', 'Task berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $title    = 'My Tasks';
        $subtitle = 'Edit Tasks';

        $todo     = Todo::where('user_id', Auth::id())->where('id', $id)->firstOrFail();
        $category = Category::where('user_id', Auth::id())->get();

        return view('todo.edit', compact('title', 'subtitle', 'todo', 'category'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:category,id',
            'priority'    => 'required|in:low,medium,high',
            'status'      => 'required|in:active,completed,pending',
            'deadline'    => 'nullable|date',
            'file'        => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg|max:5120',
        ]);

        $todo = Todo::where('user_id', Auth::id())->where('id', $id)->firstOrFail();

        // Handle file upload
        if ($request->hasFile('file')) {
            if ($todo->file_path) {
                Storage::disk('public')->delete($todo->file_path);
            }
            $todo->file_name = $request->file('file')->getClientOriginalName();
            $todo->file_path = $request->file('file')->store('todo-files', 'public');
        }

        try {
            $wasCompleted = $todo->status !== 'completed' && $request->status === 'completed';

            $todo->update([
                'category_id'  => $request->category_id,
                'title'        => $request->title,
                'description'  => $request->description,
                'priority'     => $request->priority,
                'status'       => $request->status,
                'deadline'     => $request->deadline,
                'completed_at' => $request->status === 'completed' ? now() : null,
                'file_path'    => $todo->file_path,
                'file_name'    => $todo->file_name,
            ]);

            // Log activity
            if ($wasCompleted) {
                (new LogTaskActivity)->logComplete(Auth::id());
            } else {
                (new LogTaskActivity)->logUpdate(Auth::id());
            }

            return redirect()->route('todo.index')->with('success', 'Task berhasil diperbarui.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memperbarui task, coba lagi.');
        }
    }

    public function updateStatus(Request $request, $id)
    {
        $todo = Todo::where('user_id', Auth::id())->where('id', $id)->firstOrFail();

        try {
            $wasCompleted = $todo->status !== 'completed' && $request->status === 'completed';

            $todo->update([
                'status'       => $request->status,
                'completed_at' => $request->status === 'completed' ? now() : null,
            ]);

            // Log activity
            if ($wasCompleted) {
                (new LogTaskActivity)->logComplete(Auth::id());
            } else {
                (new LogTaskActivity)->logUpdate(Auth::id());
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal update status.'], 500);
        }
    }

    public function destroy($id)
    {
        $todo = Todo::where('user_id', Auth::id())->where('id', $id)->firstOrFail();

        try {
            if ($todo->file_path && Storage::disk('public')->exists($todo->file_path)) {
                Storage::disk('public')->delete($todo->file_path);
            }

            $todo->delete();
            return redirect()->route('todo.index')->with('success', 'Task berhasil dihapus.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus task, coba lagi.');
        }
    }
}