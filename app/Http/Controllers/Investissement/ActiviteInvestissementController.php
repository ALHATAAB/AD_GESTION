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
use App\Models\DeviseAgence;
use App\Models\Investisseur;
use App\Models\TypeActiviteInvestissement;
use App\Models\MouvementCaisse;
use App\Models\ActiviteInvestissement;
use App\Models\SecteurDepense;
use App\Models\DetailActiviteInvestissement;
use App\Models\BeneficeActivite;
use App\Models\RepartitionDividende;
use App\Models\OperationInvestisseur;
use App\Models\OperationDepenseActivite;
use App\Models\OperationReglementFacture;
use App\Models\Livrer;

class ActiviteInvestissementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $id=Auth::user()->id;
        if(isset(Caisse::where('user_id',$id)->first(['id'])->id)){
            $caisse_id=Caisse::where('user_id',$id)->first(['id'])->id;
                $caisse=Caisse::find($caisse_id);
                $agence_id=Auth::user()->agence_id;
                $agence=Agence::find( $agence_id);
        $activites=ActiviteInvestissement::where('etat_activite','en cours')->where('agence_id',$agence_id)->get();
        return view('investissement.activite_investissement_liste',compact('activites','caisse'));
        }
        return view('investissement.message');
    }

    public function valider()
    {
        //
        $id=Auth::user()->id;
        if(isset(Caisse::where('user_id',$id)->first(['id'])->id)){
        $caisse_id=Caisse::where('user_id',$id)->first(['id'])->id;
                $caisse=Caisse::find($caisse_id);
                $agence_id=Auth::user()->agence_id;
                $agence=Agence::find( $agence_id);
        $activites=ActiviteInvestissement::where('etat_activite','valider')->where('agence_id',$agence_id)->get();
        return view('investissement.activite_investissement_valider',compact('activites','caisse'));
        }
        return view('investissement.message');
    }

    public function terminer()
    {
        //
        $id=Auth::user()->id;
        if(isset(Caisse::where('user_id',$id)->first(['id'])->id)){
        $caisse_id=Caisse::where('user_id',$id)->first(['id'])->id;
                $caisse=Caisse::find($caisse_id);
                $agence_id=Auth::user()->agence_id;
                $agence=Agence::find( $agence_id);
        $activites=ActiviteInvestissement::where('etat_activite','terminer')->where('agence_id',$agence_id)->get();
        return view('investissement.activite_investissement_terminer',compact('activites','caisse'));
        }
        return view('investissement.message');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $id=Auth::user()->id;

        $societe_id=Auth::user()->societe_id;

        if(isset(Caisse::where('user_id',$id)->first(['id'])->id)){
                $caisse_id=Caisse::where('user_id',$id)->first(['id'])->id;
                $caisse=Caisse::find($caisse_id);
                $agence_id=Auth::user()->agence_id;
                $agence=Agence::find( $agence_id);
            $type_activites=TypeActiviteInvestissement::where('societe_id',$societe_id)->get();
            return view('investissement.activite_investissement', compact('type_activites','caisse'));
        }
        return view('devise.message');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        /**
        * validation des champs de saisie
        */
       $request->validate([
        'type_activite'=>'required',
        'montant_decaisse'=>'required',
        'commentaire'=>'required',
        ]);

        /**
         * donnee a ajouté dans la table
         */

        $data=$request->all();
        // dd($data);
        $id=Auth::user()->id;

        if(isset(Caisse::where('user_id',$id)->first(['id'])->id)){
                $caisse_id=Caisse::where('user_id',$id)->first(['id'])->id;
                $caisse=Caisse::find($caisse_id);
                $societe_id=Auth::user()->societe_id;
                $agence_id=Auth::user()->agence_id;
                $agence=Agence::find( $agence_id);

                $compte_caisse= Caisse::where('user_id',$id)->first(['compte'])->compte;
                $investisseurs=Investisseur::where('compte_investisseur','>','0')->where('etat','1')->where('societe_id',$societe_id)->get();
                foreach($investisseurs as $investisseur)
                {
                    $date_comptable= Caisse::where('user_id',$id)->first(['date_comptable'])->date_comptable;
                    
                        $taux=DeviseAgence::where('devise_id',$investisseur->agence->devise_id)->where('agence_id',$agence_id)->get();
                        foreach($taux as $tx)
                        {
                            $capital_investisseurs=Investisseur::where('etat','1')->where('societe_id',$societe_id)->selectRaw('sum(compte_investisseur) as total')->get();
                                // $somme_total=0;
                            foreach($capital_investisseurs as $capital_investisseur)
                            {
    
                                //  dd($capital_investisseur->total,$tx->taux);
                                    $somme_total=round($capital_investisseur->total/$tx->taux);
                                // dd($somme_total);
                                    $date_comptable= Caisse::where('user_id',$id)->first(['date_comptable'])->date_comptable;
                        
                                if($capital_investisseur->total < $data['montant_decaisse'])  
                                {
    
                                    return redirect('/activite_investissement/create',)->with('danger','Le montant de l\'activité est supperieur au capital investis ');
    
                                }else{
    
                                    if($data['montant_decaisse'] > $compte_caisse)  {
    
                                        return redirect('/activite_investissement/create',)->with('danger','Le montant caisse insuffisant ');
    
                                    } else{
                                    /**
                                     * insertion des données dans la table user
                                     */
                                    ActiviteInvestissement::create([
                                        'type_activite_id'=>$data['type_activite'],
                                        'capital_activite'=>$somme_total,
                                        'montant_decaisse'=>$data['montant_decaisse'],
                                        'commentaire'=>$data['commentaire'],
                                        'taux_devise'=>$tx->taux,
                                        'user_id'=>$id,
                                        'caisse_id'=>$caisse_id,
                                        'agence_id'=>$agence_id,
                                        'date_comptable'=>$date_comptable,
                                    ]);
    
                                    $activite_id=ActiviteInvestissement::where('user_id',$id)->latest('id')->first();
    
                                    return redirect('/'.$activite_id->id.'/activite_investissement/repartition');
                                    }
                                }
                            }
                        }
                    
                        
                }


        }
        return view('devise.message');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
        $activite_investissement=ActiviteInvestissement::find($id);
        $agence_id=Auth::user()->agence_id;
        $agence=Agence::find( $agence_id);
        $devise=Devise::where('id', $agence->devise_id)->first();
        $detail_activite_investissements=DetailActiviteInvestissement::where('activite_investissement_id',$activite_investissement->id)->get();
        $operation_depenses=OperationDepenseActivite::where('activite_investissement_id',$activite_investissement->id)->get();
        $livraisons=Livrer::where('activite_id',$activite_investissement->id)->get();
        $reglements=OperationReglementFacture::where('activite_id',$activite_investissement->id)->get();
            return view('investissement.activite_investissement_show', compact(
                'detail_activite_investissements',
                'activite_investissement',
                'operation_depenses','devise','livraisons','reglements'
        ));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $user_id=Auth::user()->id;
            $caisse_id=Caisse::where('user_id',$user_id)->first(['id'])->id;
                $caisse=Caisse::find($caisse_id);
                $agence_id=Auth::user()->agence_id;
                $agence=Agence::find( $agence_id);
            $activite=ActiviteInvestissement::find($id);

            return view('investissement.benefice_investissement', compact('activite','caisse'));

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        /**
        * validation des champs de saisie
        */
        $request->validate([
        'montant_benefice'=>'required',
        ]);
        $data=$request->all();

        $activite_investissement=ActiviteInvestissement::find($id);

        $activite_investissement->update([
            'montant_benefice'=>$data['montant_benefice'],
            'etat_activite'=>'valider',
        ]);

        return redirect('/'.$id.'/activite_investissement/repartition',)->with('danger','Le benefice valider ');

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $activite_investissement=ActiviteInvestissement::find($id);

        $activite_investissement->delete();

        return redirect('/activite_investissement/create',)->with('danger','Le activité supprimer ');

    }


    public function repartition($id)
    {

        $activite_investissement=ActiviteInvestissement::find($id);

        $agence_id=Auth::user()->agence_id;
        $societe_id=Auth::user()->societe_id;
        
        $investisseurs=Investisseur::where('compte_investisseur','>','0')->where('etat','1')->where('societe_id',$societe_id)->get();
        
        return view('investissement.activite_investissement_repartition', compact(
        'investisseurs',
        'activite_investissement',
        ));



    }
   
    public function repartie(Request $request)
    {
        $id=Auth::user()->id;
        $agence_id=Auth::user()->agence_id;
        $societe_id=Auth::user()->societe_id;
        $caisse_id=Caisse::where('user_id',$id)->first(['id'])->id;

            $compte_caisse= Caisse::where('user_id',$id)->first(['compte'])->compte;
            $compte_dividende_societe= Societe::where('id',$societe_id)->first(['compte_societe'])->compte_societe;
            $compte_securite_societe= Societe::where('id',$societe_id)->first(['compte_securite'])->compte_securite;
            $date_comptable= Caisse::where('user_id',$id)->first(['date_comptable'])->date_comptable;

        $activite_investissement=ActiviteInvestissement::find($request->activite_id);

        if($activite_investissement->etat_activite=='valider'){
            
            $taux_devise=$activite_investissement->taux_devise;
            $dividende_entreprise=round($request->montant_benefice/2);
            $benefice_investisseur=round($request->montant_benefice/2);
            $benefice_securite=round($benefice_investisseur*0.1);
            $dividende_investisseur=$benefice_investisseur-$benefice_securite;
            $total_depense=$request->total_depense;
            for($i=0;$i<count($request->investisseur_id); $i++)
            {

                 $investisseurs=Investisseur::where('id',$request->investisseur_id[$i])->get();
                 
                foreach( $investisseurs as  $investisseur){

           
                    $investisseur_id   =$request->investisseur_id[$i];
                    $montant_investis  = (($request->montant_investis[$i])*$taux_devise)+$investisseur->compte_investisseur;
                    $dividende_gagner  =(($request->taux[$i])*(($dividende_investisseur*$taux_devise)))+$investisseur->compte_dividende;
                
                //     dd(
                // $investisseur_id,
                // $montant_investis,
                // $dividende_investisseur,
                // $dividende_gagner,
                // $request->taux,);

                        $data=[
                            'compte_dividende'   =>$dividende_gagner,
                            'compte_investisseur'  =>$montant_investis,
                        ];

                        Investisseur::where('id', $investisseur_id)->update($data);

                }
            }   

            $activite_investissement->update([
                'etat_activite'=>'terminer',
                'montant_benefice'=>$request->montant_benefice,
            ]);
             


            $compte_dividende_entreprise=round($compte_dividende_societe+$dividende_entreprise*$taux_devise);
            $compte_securite_societe=round($compte_securite_societe+$benefice_securite*$taux_devise);

                $societe_id=Auth::user()->societe_id;
                $societe=Societe::find($societe_id);
                $societe->update([
                    'compte_societe'=>$compte_dividende_entreprise,
                    'compte_securite'=>$compte_securite_societe,
                ]);

                // $caisse->update([
                //     'compte'=>$compte,
                //     'compte_dividende_societe'=>$compte_dividende_entreprise,
                // ]);

            return redirect('/activite_investissement/valider');
         } else{
            return redirect("/$activite_investissement->id/activite_investissement/repartition")->with('danger','Activité déjà términée ');;
        }
    }
    
    public function redemarrer($id)
    {
        $user_id=Auth::user()->id;
        $caisse_id=Caisse::where('user_id',$user_id)->first(['id'])->id;
            $caisse=Caisse::find($caisse_id);
            $agence_id=Auth::user()->agence_id;
            $agence=Agence::find( $agence_id);
        $devise=Devise::where('id', $agence->devise_id)->first();
        $activite_investissement=ActiviteInvestissement::find($id);

        $detail_activite_investissements=DetailActiviteInvestissement::where('activite_investissement_id',$id)->get();

        $secteur_depenses=SecteurDepense::all();

        $operation_depenses=OperationDepenseActivite::where('activite_investissement_id',$activite_investissement->id)->get();
        $livraisons=Livrer::where('activite_id',$activite_investissement->id)->get();
        $reglements=OperationReglementFacture::where('activite_id',$activite_investissement->id)->get();

        return view('investissement.redemarrer_activite_investissement', compact('activite_investissement',
        'caisse','detail_activite_investissements','secteur_depenses','operation_depenses','devise','livraisons', 'reglements'
        ));
    }

    public function initier(Request $request)
    {
        $id=Auth::user()->id;
        $agence_id=Auth::user()->agence_id;
        $societe_id=Auth::user()->societe_id;
        $caisse_id=Caisse::where('user_id',$id)->first(['id'])->id;

            $compte_caisse= Caisse::where('user_id',$id)->first(['compte'])->compte;
            $compte_dividende_societe= Societe::where('id',$societe_id)->first(['compte_societe'])->compte_societe;
            $compte_securite_societe= Societe::where('id',$societe_id)->first(['compte_securite'])->compte_securite;
            $date_comptable= Caisse::where('user_id',$id)->first(['date_comptable'])->date_comptable;

            $activite_investissement=ActiviteInvestissement::find($request->activite_id); 
            $detail_activite_investissement=DetailActiviteInvestissement::where('activite_investissement_id',$request->activite_id)->get();

        if($activite_investissement->etat_activite=='valider'){
            
            $taux_devise=$activite_investissement->taux_devise;
            $dividende_entreprise=round($request->montant_benefice/2);
            $benefice_investisseur=round($request->montant_benefice/2);
            $benefice_securite=round($benefice_investisseur*0.1);
            $dividende_investisseur=$benefice_investisseur-$benefice_securite;
            $total_depense=$request->total_depense;
            for($i=0;$i<count($request->investisseur_id); $i++)
            {

                 $investisseurs=Investisseur::where('id',$request->investisseur_id[$i])->get();
                 
                foreach( $investisseurs as  $investisseur){

           
                    $investisseur_id   =$request->investisseur_id[$i];
                    $montant_investis  = round(($request->montant_investis[$i])*$taux_devise)+$investisseur->compte_investisseur;
                    // $dividende_gagner  =(($request->taux[$i])*(round($dividende_investisseur*$taux_devise)))+$investisseur->compte_dividende;
                
                //     dd(
                // $investisseur_id,
                // $montant_investis,
                // $dividende_investisseur,
                // $dividende_gagner,
                // $request->taux,);

                        $data=[
                            // 'compte_dividende'   =>$dividende_gagner,
                            'compte_investisseur'  =>$montant_investis,
                        ];

                        Investisseur::where('id', $investisseur_id)->update($data);

                }
            }   
        
            $capital_investisseur=Investisseur::where('etat','1')->where('societe_id',$societe_id)->selectRaw('sum(compte_investisseur) as total')->first('total');
                    //  dd($capital_investisseur->total)  ;     
            $activite_investissement->update([
                'etat_activite'=>'en cours',
                'capital_activite'=>$capital_investisseur->total,
            ]);

            foreach($detail_activite_investissement as $sup )
            {
                $sup->delete('activite_investissement_id',$activite_investissement->id);
            }
            


            // $compte_dividende_entreprise=round($compte_dividende_societe+$dividende_entreprise*$taux_devise);
            // $compte_securite_societe=round($compte_securite_societe+$benefice_securite*$taux_devise);

            //     $societe_id=Auth::user()->societe_id;
            //     $societe=Societe::find($societe_id);
            //     $societe->update([
            //         'compte_societe'=>$compte_dividende_entreprise,
            //         'compte_securite'=>$compte_securite_societe,
            //     ]);

                // $caisse->update([
                //     'compte'=>$compte,
                //     'compte_dividende_societe'=>$compte_dividende_entreprise,
                // ]);

                return redirect("/$activite_investissement->id/activite_investissement/repartition");
         } else{
            return redirect("/activite_investissement")->with('danger','Activité déjà términée ');;
        }
    }

    public function depense_activite(Request $request, $id)
    {

        $user_id=Auth::user()->id;
        $agence_id=Auth::user()->agence_id;
        $societe_id=Auth::user()->societe_id;
        $caisse_id=Caisse::where('user_id',$user_id)->first(['id'])->id;

        if(Caisse::where('user_id',$user_id)->first(['id'])->id)
        {
            $compte_caisse= Caisse::where('user_id',$user_id)->first(['compte'])->compte;
            $compte_dividende_societe= Societe::where('id',$societe_id)->first(['compte_societe'])->compte_societe;
            $compte_securite_societe= Societe::where('id',$societe_id)->first(['compte_securite'])->compte_securite;
            $date_comptable= Caisse::where('user_id',$user_id)->first(['date_comptable'])->date_comptable;
        
            if($compte_caisse < $request->montant_depense)
            {
                return back()->with('danger','La montant caisse est insuffisant');
            }
            else
            {
                
                // dd($id,$request->secteur_id,$request->montant_depense);
                $activite_investissement=ActiviteInvestissement::find($id);
            // dd($activite_investissement->compte_activite ,$request->montant_depense);
                if($activite_investissement->compte_activite < $request->montant_depense)
                {
                    return back()->with('danger',"La montant de l'activite est insuffisant");
                }
                else
                {
                    if($request->secteur_id){
                            
                                $data=[
                                    'activite_investissement_id'   =>$id,
                                    'secteur_depense_id'           =>$request->secteur_id,
                                    'montant_depense'              =>$request->montant_depense,
                                ];
                                OperationDepenseActivite::create($data);

                                $activite_investissement->update([
                                    'compte_activite'=>$activite_investissement->compte_activite-$request->montant_depense,
                                    'total_depense'=>$activite_investissement->total_depense+$request->montant_depense,
                                ]);
                        /**
                             * mise a jour de la caisse
                            */
                            $montant_operation=$request->montant_depense;
                            $secteur_depense=SecteurDepense::find($request->secteur_id);
                            $compte=$compte_caisse-$montant_operation;
                        
                            $caisse=Caisse::find($caisse_id);

                            MouvementCaisse::create([
                                'caisse_id'=>$caisse->id,
                                'user_id'=>$user_id,
                                'description'=>'Depense de l\'activite N° '.$id.' dans le secteur : '.$secteur_depense->secteur_depense,
                                'sortie'=>$montant_operation,
                                'solde'=>$compte,
                                'date_comptable'=>$date_comptable,

                            ]);

                            $caisse->update([
                                'compte'=>$compte,
                            ]);
                            return back();        
                    }
                    return back();
                }
            }

        }
       

    }
    public function supprimer_depense($id)
    {
        $depense_activite=OperationDepenseActivite::find($id);
        $activite_investissement=ActiviteInvestissement::find($depense_activite->activite_investissement_id);
        
        
        $user_id=Auth::user()->id;
        $agence_id=Auth::user()->agence_id;
        $societe_id=Auth::user()->societe_id;
        $caisse_id=Caisse::where('user_id',$user_id)->first(['id'])->id;

        if(Caisse::where('user_id',$user_id)->first(['id'])->id)
        {
            $compte_caisse= Caisse::where('user_id',$user_id)->first(['compte'])->compte;
            $compte_dividende_societe= Societe::where('id',$societe_id)->first(['compte_societe'])->compte_societe;
            $compte_securite_societe= Societe::where('id',$societe_id)->first(['compte_securite'])->compte_securite;
            $date_comptable= Caisse::where('user_id',$user_id)->first(['date_comptable'])->date_comptable;
        
                  
                    

                                $activite_investissement->update([
                                    'compte_activite'=>$activite_investissement->compte_activite+$depense_activite->montant_depense,
                                    'total_depense'=>$activite_investissement->total_depense-$depense_activite->montant_depense,
                                ]);
                        /**
                             * mise a jour de la caisse
                            */
                            $montant_operation=$depense_activite->montant_depense;
                            $secteur_depense=SecteurDepense::find($depense_activite->secteur_depense_id);
                            $compte=$compte_caisse+$montant_operation;
                        
                            $caisse=Caisse::find($caisse_id);

                            MouvementCaisse::create([
                                'caisse_id'=>$caisse->id,
                                'user_id'=>$user_id,
                                'description'=>'Depense de l\'activite N° '.$id.' dans le secteur : '.$secteur_depense->secteur_depense.' supprimer',
                                'entree'=>$montant_operation,
                                'solde'=>$compte,
                                'date_comptable'=>$date_comptable,

                            ]);

                            $caisse->update([
                                'compte'=>$compte,
                            ]);
                            $depense_activite->delete();
                            return back();        
                    }
       



    }
    public function annuler_livraison()
    {
        dd('annuler livraison');
    }
    public function reception_produit()
    {
        dd('reception produit');
    }

    public function livraison_produit()
    {
        dd('livraison produit');
    }

}
