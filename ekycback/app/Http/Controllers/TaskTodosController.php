<?php

namespace App\Http\Controllers;

use App\Models\TaskTodos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class TaskTodosController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $taskId)
    {
        /** All Users for Authenticated user*/

        $todos = TaskTodos::with('user')->where('task_id', $taskId)->orderBy('id', 'desc')->get();
        if ($todos) {
            return response()->json([
                "code" => 200,
                "data" => $todos
            ]);
        }
        return response()->json(["code" => 400]);
        //$users = $this->user->allowedUsers();

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $taskTodos = new TaskTodos();

        $prctype = TaskTodos::create(
            [
                'task_id' => $request->taskId,
                'todo_text' => $request->todo_text,
                'created_by' => auth()->user()->id,
                'created_by_name' => auth()->user()->name,

            ]
        );

        if ($prctype->save()) {
            $messageRedis = [];
                $messageRedis['msg'] = 'data added';
                Redis::publish('loadEkycMenaul', json_encode($messageRedis, true));
            return response()->json([
                "code" => 200,
                "msg" => "data inserted successfully"
            ]);
        }

        return response()->json(["code" => 400]);
        die;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $taskTodo = TaskTodos::Where('id', $id);
        if (!$taskTodo) {
            return response()->json([
                "code" => 404,
                "msg" => "data not found"
            ]);
        }
        $isEdit = ($request->edit) ? 'yes' : '';
        $updateTodo = TaskTodos::find($id);

        if ($updateTodo->completed && $isEdit == '') {
            $message = 'undone successfully';
            $updateTodo->approved_by = auth()->user()->id;
            $updateTodo->approved_by_name = auth()->user()->name;
            $val = 0;
        } else if (!$updateTodo->completed && $isEdit == '') {
            $updateTodo->approved_by = auth()->user()->id;
            $updateTodo->approved_by_name = auth()->user()->name;
            $message = 'done successfully';
            $val = 1;
        } else if ($isEdit == 'yes') {
            //$updateTodo->completed &&
            $message = 'data updated successfully';
        } else {
            $updateTodo->approved_by = auth()->user()->id;
            $updateTodo->approved_by_name = auth()->user()->name;
            $message = 'undone successfully';
        }

        if ($isEdit != 'yes') {
            $updateTodo->completed = $val;
        }
        $updateTodo->todo_text = $request->todo['todo_text'];

        if ($updateTodo->save()) {
            return response()->json([
                "code" => 200,
                "msg" => $message
            ]);
        }

        return response()->json([
            "code" => 400,
            "msg" => ''
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $prctype = TaskTodos::Where('id', $id);

        if (!$prctype) {
            return response()->json([
                "code" => 404,
                "msg" => "data not found"
            ]);
        }

        if ($prctype->delete()) {
            return response()->json([
                "code" => 200,
                "msg" => "deleted the record"
            ]);
        }

        return response()->json(["code" => 400]);
    }
}
