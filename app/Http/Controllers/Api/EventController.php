<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EventResource;
use App\Models\Event;
use Illuminate\Http\Request;

class EventController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $query = Event::query();
        $relations = ['user','attendees','attendees.user'];

        foreach ($relations as $relation)
        {
            //it will check the query if the query in array $relations with the function shouldIncludeRelation()
            $query->when(
                $this->shouldIncludeRelation($relation),
                //this means query will load the relations that are defined For example: Event::with('user','attendees')
                fn($q)=> $q->with($relation)
            );
        }

        return EventResource::collection(
            $query->latest()->paginate()
        );
    }

    protected function shouldIncludeRelation(string $relation): bool
    {
        $include = request()->query('include');

        if (!$include)
        {
            return false;
        }

        $relations = array_map('trim', explode(',', $include));

        return in_array($relation, $relations);

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $event = Event::create([
            ...$request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after:start_date'
            ]),
            'user_id' => 1
        ]);

        return $event;
    }

    /**
     * Display the specified resource.
     */
    public function show(Event $event)
    {
        $event->load('user','attendees');
        return new EventResource($event);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Event $event)
    {
        $event->update(
            $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after:start_date'
        ]));

        return new EventResource($event);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Event $event)
    {
        $event -> delete();
        return response()->json([
            "message" => "Event has been deleted successfully"
        ]);
    }
}
