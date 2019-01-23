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
                          {!! Form::open(array('url' => 'settings')) !!}
                          <div class="row p-1">
                            <div class="col-sm col-lg-4">
                              {!! Form::label('key', 'Active Campaign API key') !!}
                            </div>
                            <div class="col-sm">
                              {!! Form::text('key', 'banan & beard') !!}
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
