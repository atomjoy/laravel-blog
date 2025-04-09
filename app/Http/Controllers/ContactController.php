<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Contact;
use App\Http\Requests\StoreContactRequest;
use App\Http\Requests\UpdateContactRequest;
use App\Http\Resources\ContactCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class ContactController extends Controller
{
	/**
	 * Display a listing of the resource.
	 */
	public function index()
	{
		Gate::authorize('viewAny', Contact::class);

		$perpage = request()->integer('perpage', default: 6);

		return  new ContactCollection(Contact::latest('id')->paginate($perpage));
	}

	/**
	 * Show the form for creating a new resource.
	 */
	public function create()
	{
		// Route not exists
	}

	/**
	 * Store a newly created resource in storage.
	 */
	public function store(StoreContactRequest $request)
	{
		// Route not exists
	}

	/**
	 * Display the specified resource.
	 */
	public function show(Contact $contact)
	{
		Gate::authorize('view', $contact);

		return $contact;
	}

	/**
	 * Show the form for editing the specified resource.
	 */
	public function edit(Contact $contact)
	{
		// Route not exists
	}

	/**
	 * Update the specified resource in storage.
	 */
	public function update(UpdateContactRequest $request, Contact $contact)
	{
		Gate::authorize('update', $contact);

		$contact->update($request->safe()->only(['note']));

		return response()->json([
			'message' => 'Updated',
			'data' => $contact,
		]);
	}

	/**
	 * Remove the specified resource from storage.
	 */
	public function destroy(Contact $contact)
	{
		Gate::authorize('delete', $contact);

		try {
			if (Storage::disk('local')->exists($contact->file)) {
				Storage::disk('local')->delete($contact->file);
			}
		} catch (\Throwable $e) {
			report($e);
		}

		$contact->delete();

		return response()->json(['message' => 'Deleted']);
	}

	/**
	 * Display the specified resource.
	 */
	public function count(Admin $admin)
	{
		return response()->json([
			'message' => 'Counted',
			'count' => Contact::count() ?? 0
		]);
	}

	/**
	 * Download file.
	 */
	public function download(Admin $admin)
	{
		try {
			$path =  request('path') ?? '';

			if (Storage::disk('local')->exists($path)) {
				return Storage::disk('local')->download($path);
			}
		} catch (\Throwable $e) {
			report($e);
		}

		return response()->json([
			'message' => 'Invalid path',
		]);
	}
}
