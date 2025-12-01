@extends('emails.template')
@section('message')
        @php
           echo nl2br($html);
       @endphp
    @endsection
