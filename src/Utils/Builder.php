<?php

namespace Adtyrzq\Utils;

use App\Utils\Upload;
use Illuminate\Support\Facades\Validator as FacadesValidator;
use Illuminate\Support\Facades\Hash;

trait Builder
{
    use Upload;

    public $model;

    public $model_table;

    public $model_columns;

    /**
     * Get Model Fields
     *
     * @return \Illuminate\Validation\Validator
     */
    public function validateFields($fields)
    {
        $rules = [];

        if (request()->has('_action') && request()->get('_action') == 'edit') {
            foreach ($fields as $key => $field) {
                if (isset($field['edit']) && !$field['edit']) {
                    unset($fields[$key]);
                }
            }
        }

        foreach ($fields as $field) {
            $rules[$field['key']] = $field['validation'] ?? 'nullable';
        }

        $validator = FacadesValidator::make(request()->all(), $rules);

        return $validator;
    }

    /**
     * Get data from request based on fields
     *
     * @return array
     */
    public function getDataFromRequest($fields)
    {
        $data = [];

        foreach ($fields as $field) {
            $field_type = explode('|', $field['type']);

            if ($field_type[0] == 'file') {
                $file_store = $field['store'] ?? 'public';

                $data[$field['key']] = $file_store == 'public' ?
                    $this->uploadFile(request($field['key']), $field['folder']) :
                    $this->uploadPrivateFile(request($field['key']), $field['folder']);
            } else {
                $is_password = $field_type[1] == 'password';

                $data[$field['key']] = $is_password ?
                    Hash::make(request($field['key'])) :
                    request($field['key']);
            }
        }

        return $data;
    }

    /**
     * Route Flash
     *
     * @return void
     */
    public function routeFlash($commit, $action = null)
    {
        $status = $commit ? 'success' : 'error';

        if ($action == 'create') {
            $message = $commit ? 'Create successfully' : 'Failed to create';
        } else if ($action == 'update') {
            $message = $commit ? 'Update successfully' : 'Failed to update';
        } else if ($action == 'delete') {
            $message = $commit ? 'Delete successfully' : 'Failed to delete';
        } else {
            $message = $commit ? 'Saved successfully' : 'Failed to save';
        }

        if (request()->has('redirect')) {
            return to_route(request('redirect'))->with($status, $message);
        }

        return redirect()->back()->with($status, $message);
    }

    /**
     * Get default view
     *
     * @return void
     */
    public function defaultView($view, $params = [])
    {
        $query = $this->model->query();

        if ($view == 'admin.default.table') {
            if (request()->has('search')) {
                $search = request('search');

                $query->where(function ($query) use ($search) {
                    foreach ($this->model_columns as $column) {
                        if (in_array($column, $this->model->ignored_search)) {
                            continue;
                        }
                        $query->orWhere($column, 'like', "%$search%");
                    }
                });
            }

            if (isset($params['order'])) {
                $query->orderBy($params['order'][0], $params['order'][1]);
            }

            $params['data'] = $query->paginate(10);
        } else if ($view == 'admin.default.form') {

            if (isset($params['model'])) {
                $params['model'] = $params['model']->toArray();
            }

            $params['fields'] = $this->model->fields();
        }

        if (isset($params['actions'])) {
            $ignored_actions = $params['actions'];

            $params['actions'] = $this->model->routes();

            foreach ($ignored_actions as $key => $value) {
                if (!$value) {
                    unset($params['actions'][$key]);
                }
            }
        } else {
            $params['actions'] = $this->model->routes();
        }

        return view($view, compact('params'));
    }

    /**
     * Default store method
     *
     * @return void
     */
    public function store()
    {
        $fields = $this->model->fields();

        $errors = $this->validateFields($fields);

        if (!empty($errors->errors()->all())) {
            return redirect()->back()->withErrors($errors);
        }

        $data = $this->getDataFromRequest($fields);

        return $this->routeFlash($this->model->create($data), 'create');
    }

    /**
     * Default update method
     *
     * @return void
     */
    public function update()
    {
        $fields = $this->model->fields();

        $errors = $this->validateFields($fields);

        if (!empty($errors->errors()->all())) {
            return redirect()->back()->withErrors($errors);
        }

        $data = $this->getDataFromRequest($fields);

        $model = $this->model->where(request()->get('_key'), request()->get('_value'))->first();

        return $this->routeFlash($model->update($data), 'update');
    }

    /**
     * Default destroy method
     *
     * @return void
     */
    public function destroy()
    {
        $model = $this->model->where(request()->get('_key'), request()->get('_value'))->first();

        $fields = $this->model->fields();

        foreach ($fields as $field) {
            $field_type = explode('|', $field['type'])[0];

            if ($field_type == 'file') {
                $file_store = $field['store'] ?? 'public';

                if ($file_store == 'private') {
                    $this->destroyPrivateImage($model->{$field['key']});
                } else {
                    $this->destroyFile($model->{$field['key']});
                }
            }
        }

        return $this->routeFlash($model->delete(), 'delete');
    }
}
