@foreach($data as $p)
    <li>
        @if(isset($p->name))
        <label class="control-label">
            <div class="styled-checkbox">
                <input type="checkbox" id="{{ implode('_', explode(' ', $p->name)) }}" name="permission[]" value="{{ $p->value }}" {{ is_array($access) && in_array($p->value, $access) ? 'checked' : ($user->type == 11 || $user->type == 33 ? 'checked' : '') }}>
                <label for="{{ implode('_', explode(' ', $p->name)) }}"></label>
            </div>
            {{$p->name}}
        </label>
        @elseif(isset($p->text))
            <ul class="sub-permission">
                <li>
                <span class="title text text-bold">
                    <a class="tree-view" href="#" data-open="0">
                        <i class="fa fa-plus fa-xs"></i>
                    </a>&nbsp;{{$p->text}}
                </span>
                    <ul style="display: none">
                        @include('User.permission_partial',['data'=>$p->actions])
                    </ul>
                </li>
            </ul>
        @endif
    </li>
@endforeach