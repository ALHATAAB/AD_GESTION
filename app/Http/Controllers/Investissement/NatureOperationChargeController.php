<?php

namespace App\Http\Controllers\Investissement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use App\Models\Caisse;
use App\Models\Societe;
use App\Models\Devise;
use App\Models\Agence;
use App\Models\Investisseur;
use App\Models\TypeActiviteInvestissement;
use App\Models\NatureOperationCharge;

class NatureOperationChargeController extends Controller
{
   /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
            $nature_operation_charges=NatureOperationCharge::all();
        return view('investissement.nature_operation_charge',compact('nature_operation_charges'));

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        /**
        * validation des champs de saisie
        */
       $request->validate([
           'nature_operation_charge'=>'required',
       ]);
       /**
        * donnee a ajouté dans la table
        */

       $data=$request->all();
       //dd($data);
       /**
        * insertion des données dans la table user
        */
        NatureOperationCharge::create([
           'nature_operation_charge'=>$data['nature_operation_charge'],
       ]);
       return redirect('/nature_operation_charge')->with('success',"operation ajouté avec succès");
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
