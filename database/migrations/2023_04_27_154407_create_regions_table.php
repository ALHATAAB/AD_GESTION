<?php
use App\Models\Region;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('position')->nullable();
            $table->timestamps();
        });

        $regions=['Niger','Nigeria','Mali','Ghana','Tchad','Lomé','Burkina','Cote d\'ivoire','Chine','France'];
        foreach($regions as $region){
            Region::create([
                'nom'=>$region,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('regions');
    }
};
