@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">Settings</div>

                <div class="card-body">
                  @if (session('status'))
                  <div class="alert alert-success" role="alert">
                    {{ session('status') }}
                  </div>
                  @endif

                  <div class="container">
                    {!! Form::open(['url' => 'settings/save']) !!}
                    @if ($errors->any())
                    <div class="row p-1">
                      <div class="col-sm">
                        <div class="alert alert-danger" role="alert">
                          Please fix the following errors
                        </div>
                      </div>
                    </div>
                    <div class="row p-1">
                      @if($errors->has('general'))
                      <div class="col-sm">
                        @foreach ($errors->get('general') as $error)
                        <div class="alert alert-danger" role="alert">{{ $error }}</div>
                        @endforeach
                      </div>
                      @endif
                    </div>
                    @endif
                    
                    {!! csrf_field() !!}
                    <div class="row p-1">
                      <div class="col-sm col-lg-4">
                        {!! Form::label('activecampaign_account', 'Active Campaign account ID') !!}
                      </div>
                      <div class="col-sm">
                        {!! Form::text('activecampaign_account', $activecampaign_account) !!}
                        @if($errors->has('activecampaign_account'))
                        <span class="help-block">{{ $errors->first('activecampaign_account') }}</span>
                        @endif
                      </div>
                    </div>
                    <div class="row p-1">
                      <div class="col-sm col-lg-4">
                        {!! Form::label('activecampaign_token', 'Active Campaign API key') !!}
                      </div>
                      <div class="col-sm">
                        {!! Form::text('activecampaign_token', $activecampaign_token) !!}
                        @if($errors->has('activecampaign_token'))
                        <span class="help-block">{{ $errors->first('activecampaign_token') }}</span>
                        @endif
                      </div>
                    </div>
                    <div class="row p-1">
                      <div class="col-sm">
                        {!! Form::submit('Save'); !!}
                      </div>
                    </div>
                    {!! Form::close() !!}
                  </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
