<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserController extends Controller
{
    // ───────────────────────────────────────
    // 5. PROFIL — عرض
    // ───────────────────────────────────────
    public function getProfile()
    {
        $user = JWTAuth::parseToken()->authenticate();
        return response()->json([
            'id'          => $user->id,
            'nom'         => $user->nom,
            'prenom'      => $user->prenom,
            'email'       => $user->email,
            'role'        => $user->role,
            'github_link' => $user->github_link,
        ]);
    }

    // ───────────────────────────────────────
    // 5. PROFIL — تعديل اسم
    // ───────────────────────────────────────
    public function updateProfile(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        $request->validate([
            'nom'    => 'required|string',
            'prenom' => 'required|string',
        ]);
        $user->update(['nom' => $request->nom, 'prenom' => $request->prenom]);
        return response()->json(['message' => 'Profil mis à jour.']);
    }

    // ───────────────────────────────────────
    // 5. PROFIL — تغيير كلمة السر
    // ───────────────────────────────────────
    public function changePassword(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        $request->validate([
            'ancien_mot_de_passe'  => 'required',
            'nouveau_mot_de_passe' => [
                'required','min:8',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/'
            ],
        ]);

        if (!Hash::check($request->ancien_mot_de_passe, $user->mot_de_passe))
            return response()->json(
                ['message' => 'Le mot de passe actuel est incorrect.'], 400
            );

        $user->update([
            'mot_de_passe' => Hash::make($request->nouveau_mot_de_passe)
        ]);
        return response()->json(['message' => 'Mot de passe modifié avec succès.']);
    }

    // ───────────────────────────────────────
    // 6. ADMIN — قائمة المستخدمين
    // ───────────────────────────────────────
    public function getAllUsers()
    {
        $users = User::select(
            'id','nom','prenom','email',
            'role','statut','github_link','created_at'
        )->get();
        return response()->json($users);
    }

    // ───────────────────────────────────────
    // 6. ADMIN — قبول أو رفض حساب
    // ───────────────────────────────────────
    public function validateUser(Request $request, $id)
    {
        $request->validate(['action' => 'required|in:accepter,rejeter']);
        $user = User::findOrFail($id);

        if ($request->action === 'accepter') {
            $user->update(['statut' => 'actif']);
            Mail::raw(
                "Bonjour {$user->prenom}, votre compte a été accepté. Vous pouvez vous connecter.",
                fn($m) => $m->to($user->email)->subject('Compte activé')
            );
            return response()->json(['message' => 'Compte activé.']);
        }

        $user->update(['statut' => 'rejete']);
        Mail::raw(
            "Bonjour {$user->prenom}, votre compte a été rejeté. Contactez l'administrateur.",
            fn($m) => $m->to($user->email)->subject('Compte rejeté')
        );
        return response()->json(['message' => 'Compte rejeté.']);
    }

    // ───────────────────────────────────────
    // 6. ADMIN — تعديل مستخدم
    // ───────────────────────────────────────
    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $request->validate([
            'nom'    => 'required|string',
            'prenom' => 'required|string',
            'email'  => 'required|email|unique:users,email,'.$id,
            'role'   => 'required|in:testeur,developpeur,admin',
        ]);
        $user->update($request->only('nom','prenom','email','role','github_link'));
        return response()->json(['message' => 'Utilisateur mis à jour.']);
    }
    public function changeEmail(Request $request)
{
    $user = JWTAuth::parseToken()->authenticate();
    $request->validate([
        'new_email'    => 'required|email|unique:users,email',
        'mot_de_passe' => 'required',
    ], [
        'new_email.unique' => 'Cette adresse email est déjà associée à un compte.',
    ]);

    if (!Hash::check($request->mot_de_passe, $user->mot_de_passe))
        return response()->json(
            ['message' => 'Le mot de passe actuel est incorrect.'], 400
        );

    $user->update(['email' => $request->new_email]);
    return response()->json(['message' => 'Adresse email modifiée avec succès.']);
}

public function disableUser($id)
{
    $user = User::findOrFail($id);

    if ($user->role === 'admin')
        return response()->json(
            ['message' => "Impossible de désactiver un administrateur."], 403
        );

    $user->update(['statut' => 'desactive']);
    return response()->json(['message' => 'Compte désactivé.']);
}

public function enableUser($id)
{
    $user = User::findOrFail($id);
    $user->update(['statut' => 'actif']);
    return response()->json(['message' => 'Compte réactivé.']);
}
}







