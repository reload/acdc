<?php

namespace App\Http\Requests;

use App\ActiveCampaign;
use Illuminate\Foundation\Http\FormRequest;
use Throwable;

class StoreActiveCampaignSettings extends FormRequest
{
    protected $activeCampaign;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    // public function authorize()
    // {
    //     return false;
    // }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(ActiveCampaign $activeCampaign)
    {
        $this->activeCampaign = $activeCampaign;
        return [
            'activecampaign_account' => 'required|integer',
            'activecampaign_token' => 'required|max:255|alpha_dash',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            try {
                $data = $validator->getData();
                $this->activeCampaign->withCreds(
                    $data['activecampaign_account'],
                    $data['activecampaign_token'])
                    ->ping();
            }
            catch (Throwable $e) {
                $validator->errors()->add('general', 'Creds invalid: ' . $e->getMessage());
            }
        });
    }
}
