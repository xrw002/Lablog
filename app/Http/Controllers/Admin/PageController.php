<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\Extensions\Tool;
use App\Http\Requests\Page\Store;
use App\Models\Page;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PageController extends Controller
{
    /**
     * @var Page
     */
    protected $page;

    /**
     * PageController constructor.
     * @param Page $page
     */
    public function __construct(Page $page)
    {
        $this->page = $page;
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function manage(Request $request)
    {
        $keyword = $request->get('keyword') ?? '';
        $map = [];
        $keyword ? array_push($map, ['title', 'like', '%' . $keyword . '%']) : null;
        $pages =  $this->page
            ->query()
            ->select('id', 'title','status','click', 'created_at')
            ->where($map)
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        return view('admin.page', compact('pages'));

    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create()
    {
        return view('admin.page-create');
    }

    /**
     * @param Store $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Store $request)
    {
        $this->page->storeData($request->all());
        operation_event(auth()->user()->name,'添加单页');
        return redirect()->route('page_manage');
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit($id)
    {
        $page = $this->page->query()->find($id);
        return view('admin.page-edit', compact('page'));
    }

    /**
     * @param Store $request
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Store $request, $id)
    {
        $data = $request->except('_token');
        unset($data['editormd_id-html-code']);
        $data['html'] = Tool::markdown2Html($data['content']);
        $this->page->updateData(['id' => $id], $data);
        operation_event(auth()->user()->name,'编辑单页');
        return redirect()->route('page_manage');
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function delete(Request $request)
    {
        $data = $request->only('pid');
        $arr = explode(',', $data['pid']);
        $map = [
            'id' => ['in', $arr]
        ];
        $this->page->destroyData($map);
        operation_event(auth()->user()->name,'软删除单页');
        return redirect()->back();
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function trash()
    {
        $pages = $this->page->query()
            ->select('id', 'title', 'deleted_at')
            ->orderBy('deleted_at', 'desc')
            ->onlyTrashed()
            ->paginate(10);
        return view('admin.page-trash', compact('pages'));
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function restore(Request $request)
    {
        $data = $request->only('pid');
        $arr = explode(',', $data['pid']);
        if (!$this->page->query()->whereIn('id', $arr)->restore()) {
            show_message('恢复失败', false);
            return redirect()->back();
        }
        show_message('恢复成功');
        operation_event(auth()->user()->name,'恢复软删除单页');
        return redirect()->back();
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request)
    {
        $data = $request->only('pid');
        $arr = explode(',', $data['pid']);
        if (!$this->page->query()->whereIn('id', $arr)->forceDelete()) {
            show_message('彻底删除失败', false);
            return redirect()->back();
        }
        operation_event(auth()->user()->name,'完全删除单页');
        return redirect()->back();
    }
}
