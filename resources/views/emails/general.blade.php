@extends('emails.template')
@section('message')
{!! nl2br(e($html ?? '')) !!}
@endsection
