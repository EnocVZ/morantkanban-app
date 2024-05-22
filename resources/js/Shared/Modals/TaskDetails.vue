<template>
    <Head v-if="!loading" :title="__(task.title + ' | ' + task.project.title)" />
    <div class="task__details">
        <div class="wrapper" id="modal">
            <div role="alert" class="container">
                <div v-if="loading" class="content">
                    <div role="status" class="td__loader">
                        <div class="__f"><div><div class="i__1" /><div class="i__2" /></div><div class="i__r" /></div>
                        <div class="__f"><div><div class="i__1" /><div class="i__2" /></div><div class="i__r" /></div>
                        <div class="__f"><div><div class="i__1" /><div class="i__2" /></div><div class="i__r" /></div>
                        <div class="__f"><div><div class="i__1" /><div class="i__2" /></div><div class="i__r" /></div>
                        <div class="__f"><div><div class="i__1" /><div class="i__2" /></div><div class="i__r" /></div>
                        <div class="__f"><div><div class="i__1" /><div class="i__2" /></div><div class="i__r" /></div>
                        <div class="__f"><div><div class="i__1" /><div class="i__2" /></div></div>
                        <div class="__f"><div><div class="i__1" /><div class="i__2" /></div></div>
                        <div class="__f"><div><div class="i__1" /><div class="i__2" /></div></div>
                        <span class="sr-only">Loading...</span>
                    </div>
                </div>
                <div v-else>
                    <div class="content">
                        <div v-if="task.cover" ref="t__cover" class="t__cover" :style="{backgroundImage: 'url('+task.cover.path+')'}"></div>
                        <div v-if="task.is_archive" class="archive___task">
                            <icon name="archive" />
                            {{ __('This task is archived.') }}
                        </div>
                        <div class="close_area">
                            <div class="wrap">
                                <span v-if="isPopup" @click="$emit('closeModal', true)" class="close__b">
                                    <icon class="h-6 w-6" name="close" />
                                </span>
                                <button v-else @click="goToLink(route(view === 'table'?'projects.view.table':'projects.view.board', task.project.slug || task.project.id))" class="close__b">
                                    <icon class="h-6 w-6" name="close" />
                                </button>
                            </div>
                        </div>
                        <div class="mv__card" v-if="showMoveCard" :class="{'!left-auto right-6 top-23':is_move}">
                            <h4 class="text-center mb-3 font-bold">{{ __('Move Card') }}</h4>
                            <div class="close__b absolute cursor-pointer hover:bg-gray-200 top-3 right-3 p-1.5 rounded" @click="showMoveCard = false;is_move = false"><icon class=" w-4 h-4" name="close" /></div>
                            <span class="title mt-4 mb-1 font-bold">{{ __('Select a destination') }}</span>
                            <div class="td__btn relative flex flex-col rounded bg-gray-100 mb-3 px-3 py-2.5">
                                <span class="mb-1">{{ __('Project') }}</span>
                                <span class="text-[14px] font-bold">{{ getSelectedProject().title }}</span>
                                <select class="absolute left-0 top-0 opacity-0 w-full cursor-pointer h-[50px] z-2" v-model="move_object.project_id">
                                    <option v-for="project in this.projects" :value="project.id">{{ project.title }}</option>
                                </select>
                            </div>
                            <div class="flex gap-2">
                                <div class="td__btn relative flex flex-col w-[70%] rounded bg-gray-100 px-3 py-2.5">
                                    <span class="mb-1">{{ __('List') }}</span>
                                    <span class="text-[14px] font-bold">{{ getSelectedList().title }}</span>
                                    <select class="absolute left-0 top-0 opacity-0 w-full cursor-pointer h-[50px] z-2" v-model="move_object.list_id" @change="move_object.order=move_object.list_id === this.task.list_id?this.task.order: 1">
                                        <option v-for="list_item in getSelectedProjectLists()" :value="list_item.id">{{ list_item.title }}</option>
                                    </select>
                                </div>
                                <div class="td__btn relative flex flex-col w-[30%] rounded bg-gray-100 px-3 py-2.5">
                                    <span class="mb-1">{{ __('Position') }}</span>
                                    <span class="text-[14px] font-bold">{{ move_object.order }}</span>
                                    <select class="absolute left-0 top-0 opacity-0 w-full cursor-pointer h-[50px] z-2" v-model="move_object.order">
                                        <option v-for="list_item in [...Array(getSelectedListPostions()).keys()].map(x => ++x)" :value="list_item">{{ list_item }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="flex justify-between items-center action__buttons mt-3">
                                <button type="button" class="small save" @click="moveTask()">{{ __('Move') }}</button>
                            </div>
                        </div>
                        <div class="m__body">
                            <main class="main">
                                <div class="s__1">
                                    <div class="checklist-box">
                                        <input type="checkbox" :checked="!!task.is_done" @change="saveTask({is_done: $event.target.checked})" />
                                        <icon name="checklist_box" />
                                    </div>
                                    <div class="t__l">
                                        <h2 class="__t" contenteditable="true" @keypress="saveTitle($event)" @blur="saveTitle($event)">
                                            {{ task.title }}
                                        </h2>
                                        <span class="text-xs">in list <span class="cursor-pointer underline" @click="displayMoveCard()">{{ task.list.title }}</span> </span>

                                        <div class="flex flex-col mt-5">
                                            <span class="text-xs font-bold mb-1">{{ __('Labels') }}</span>
                                            <div class="list_labels flex flex-wrap gap-1">
                                                <button @click="showLabelBox = true" class="label_button" v-for="(task_label, label_index) in task.task_labels" :style="{ background: task_label.label.color }" :aria-label="task_label.label.name" data-a="">{{ task_label.label.name }}</button>
                                                <button @click="showLabelBox = true" class="label_button bg-gray-200 hover:bg-gray-300"><icon class="" name="plus" /></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="fixed flex w-[300px] z-10 text-sm flex-col bg-white px-4 py-4 rounded shadow" v-if="showLabelBox">
                                    <h4 class="text-center mb-3 font-bold">{{ __('Labels') }}</h4>
                                    <div class="absolute cursor-pointer hover:bg-gray-200 top-3 right-3 p-1.5 rounded" @click="showLabelBox = false" >
                                        <icon class=" w-4 h-4" name="close" />
                                    </div>
                                    <input v-model="label_search" class="border-[2px] px-2 py-1 border-gray-400 rounded-[3px]" placeholder="Search labels" />
                                    <ul class="flex flex-col mt-3 gap-3 max-h-[200px] overflow-y-auto">
                                        <li v-for="(lab, lab_index) in searchLabel(label_search)">
                                            <label class="flex gap-1">
                                                <input class="w-5 mr-2 cursor-pointer" type="checkbox" :checked="task_label_ids().includes(lab.id)" @change="addLabelToTask($event.target.checked, lab.id)">
                                                <span class="w-full px-3 py-2 rounded cursor-pointer hover:opacity-80" :style="{background: lab.color}" :tabindex="lab_index" :aria-label="lab.name" data-color="orange">{{ lab.name }}</span>
                                                <button class="p-3 hover:bg-gray-200 rounded" type="button" :tabindex="lab_index" @click="label = lab; showLabelBox = false; showEditLabelBox = true;">
                                                    <icon class="w-3 h-3" name="edit" />
                                                </button>
                                            </label>
                                        </li>
                                    </ul>
                                    <button class="w-full mt-4 px-3 py-2 rounded cursor-pointer bg-gray-300 hover:opacity-80" @click="showLabelBox = false; showEditLabelBox = true; label = {}"> {{ __('Create a new label') }} </button>
                                </div>
                                <div class="fixed flex w-[300px] z-10 text-sm flex-col bg-white px-4 py-4 rounded shadow" v-if="showEditLabelBox">
                                    <div class="absolute cursor-pointer hover:bg-gray-200 top-3 left-3 p-1.5 rounded" @click="showEditLabelBox = false;showLabelBox = true"><icon class=" w-4 h-4" name="arrow-left" /></div>
                                    <h4 class="text-center mb-3 font-bold">{{ __('Edit Labels') }}</h4>
                                    <div class="absolute cursor-pointer hover:bg-gray-200 top-3 right-3 p-1.5 rounded" @click="showEditLabelBox = false"><icon class=" w-4 h-4" name="close" /></div>
                                    <span class="w-full px-3 py-2 rounded cursor-pointer bg-gray-100 hover:opacity-80" :style="{background: label.color}" :tabindex="0" :aria-label="label.name">{{ label.name }}</span>
                                    <span class="title mt-4 font-bold mb-2">{{ __('Title') }}</span>
                                    <input class="border-[2px] px-2 py-1 border-gray-400 rounded-[3px]" placeholder="" v-model="label.name" />
                                    <span class="title mt-4 mb-1 font-bold">{{ __('Select a color') }}</span>
                                    <div class="color__wrapper grid gap-1 mb-2">
                                        <div v-for="color in colors" class="h-8 box cursor-pointer">
                                            <div class="w-full h-full border-[2px] rounded border-transparent hover:border-red-600" :title="color.name" :aria-label="color.name" :style="{backgroundColor:color.color}" @click="label.color = color.color"></div>
                                        </div>
                                    </div>
                                    <div class="flex justify-between items-center action__buttons mt-2">
                                        <button type="button" class="small save" @click="saveLabel(label)">{{ __('Save') }}</button>
                                        <button v-if="label.id" @click="deleteLabel(label.id);showEditLabelBox=false;showLabelBox=true" type="button" class="small cancel">{{ __('Delete') }}</button>
                                    </div>
                                </div>

                                <section class="s__2">
                                    <div class="__details_top">
                                        <icon name="details" />
                                        <div class="flex-1">
                                            <span class="text-sm font-medium">{{ __('Description') }}</span>
                                        </div>
                                        <icon @click="toggleDetails()" class="w-4 h-4 ml-auto cursor-pointer" name="edit" />
                                    </div>
                                    <div class="__details">
                                        <div v-if="!editDescription" class="prose pt-4 text-sm cursor-pointer" @click="toggleDetails()" v-html="task.description || 'Add more details...'"></div>
                                        <section class="mt-4" v-if="editDescription">
                                            <quill-editor ref="editDescription" @ready="onEditorReady" class="task__description" v-model:content="task.description" :options="editorOptions" contentType="html" theme="snow" />
                                            <div class="mt-2">
                                                <button type="button" class="inline-flex items-center rounded border border-gray-300 bg-indigo-600 text-white px-2.5 py-1.5 text-xs font-medium shadow-sm hover:bg-gray-50 hover:text-dark focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2" @click="saveDetails();editDescription = false">{{ __('Save') }}</button>
                                                <button @click="editDescription = false" type="button" class="inline-flex items-center rounded border border-transparent hover:border-gray-300 bg-white px-2.5 py-1.5 text-xs font-medium hover:bg-gray-50 hover:text-gray-900 focus:outline-none focus:ring-0 ltr:ml-1 rtl:mr-1">{{ __('Cancel') }}</button>
                                            </div>
                                        </section>
                                    </div>

                                </section>

                                <section class="mt-6" id="checklist">
                                    <div>
                                        <div class="flex">
                                            <icon class="w-5 h-5 mr-3" name="checklist" />
                                            <div class="flex-1 border-b pb-2">
                                                <span class="text-sm font-medium">{{ __('Checklist') }}</span>
                                                <span class="ml-2 text-sm font-light">{{ checklistDoneCount(task.checklists) }}/{{ task.checklists.length }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="pl-8 pt-4">
                                        <div class="space-y-4">
                                            <div v-for="(check_list, c_index) in task.checklists" class="group relative flex items-center">
                                                <div class="checklist-box2" v-if="!check_list.modify">
                                                    <input class="inp-cbx" :id="'cbx-' + check_list.id" :checked="!!check_list.is_done" @click="check_list.is_done = $event.target.checked;saveCheckList(check_list.id, {is_done: check_list.is_done})" type="checkbox" style="display: none;"/>
                                                    <label class="cbx" :for="'cbx-' + check_list.id">
                                                        <span>
                                                          <icon class="w-4 h-4" name="checklist_box_2" />
                                                        </span>
                                                        <span class="text-sm">{{ check_list.title }}</span>
                                                    </label>
                                                </div>
                                                <div class="checklist-box2 pl-6 w-full" v-if="check_list.modify">
                                                    <input :id="'modify_'+check_list.id" class="border rounded p-2 text-sm bg-white w-full" v-model="check_list.title" @keyup="$event.keyCode === 13?modifyCheckListSubmit(check_list, c_index, task.checklists):''" />
                                                    <div class="flex">
                                                        <div class="flex items-center action__buttons mt-2">
                                                            <button type="button" class="small save" @click="modifyCheckListSubmit(check_list, c_index, task.checklists)">
                                                                {{ __('Save') }}</button>
                                                            <button @click="check_list.modify = false" type="button" class="small cancel">
                                                                {{ __('Cancel') }}</button>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="absolute right-0 hidden pl-4 group-hover:flex" v-if="!check_list.modify">
                                                    <icon class="w-4 h-4 mr-3 cursor-pointer" name="edit" @click="modifyCheck(check_list)" />
                                                    <icon class="w-4 h-4 cursor-pointer" name="trash" @click="deleteCheckList(check_list.id, c_index, task.checklists)" />
                                                </div>
                                            </div>
                                            <div v-show="newCheckList" class="group relative flex">
                                                <div class="checklist-box2 pl-6 w-full">
                                                    <input class="border rounded p-2 text-sm bg-white w-full" ref="ncl" v-model="new_chek_list.title" @keyup="inputNewChecklistAction(new_chek_list, $event)" />
                                                    <div class="flex">
                                                        <div class="flex items-center action__buttons mt-2">
                                                            <button type="button" class="small save" @click="inputNewChecklistAction(new_chek_list)">
                                                                {{ __('Save') }}</button>
                                                            <button @click="newCheckList = false" type="button" class="small cancel">{{ __('Cancel') }}</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <button class="group flex items-center mt-6" @click="openNewChecklist()">
                                            <icon class="w-4 h-4" name="add" />
                                            <span class="pl-2 text-sm group-hover:opacity-70">{{ __('Add a new item') }}</span>
                                        </button>
                                    </div>
                                </section>

                                <section class="mt-8">
                                    <div>
                                        <div class="flex">
                                            <icon class="w-4 h-4 mr-3 mt-1" name="attachment" />
                                            <div class="flex-1 border-b pb-2">
                                                <span class="text-sm font-medium ">{{ __('Attachments') }}</span>
                                                <span class="ml-2 text-sm font-light ">{{ task.attachments.length }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="pl-8 pt-4">
                                        <div class="flex flex-col gap-2 text-sm">
                                            <div v-for="(attachment, a_index) in task.attachments" class="__attachment flex gap-3 py-4 hover:bg-gray-100">
                                                <div class="preview" :aria-label="attachment.name">
                                                    <div v-if="['jpeg','png','gif','jpg','svg','webp','bmp'].includes(attachment.name.split('.').pop())" class="" :style="{'backgroundImage': `url(${attachment.path})`}" :alt="attachment.name" />
                                                    <div v-else>{{ attachment.name.split('.').pop() }}</div>
                                                </div>
                                                <div class="flex flex-col gap-2 w-full">
                                                    <div class="font-bold"><a :href="attachment.path" target="_blank">{{ attachment.name }}</a></div>
                                                    <div class="flex gap-3">
                                                        <span :aria-label="moment(attachment.created_at).format('MMMM D, YYYY h:mm A')">{{ moment(attachment.created_at).format('[Added] MMM D, YYYY [at] h:mm A') }} </span>
                                                        -
                                                        <span class="flex underline cursor-pointer" @click="deleteAttachment(attachment.id, a_index)">{{ __('Delete') }}</span>
                                                    </div>
                                                    <div class="flex gap-3">
                                                        <div v-if="!task.cover && ['jpeg','png','gif','jpg','svg','webp','bmp'].includes(attachment.name.split('.').pop())" class="cover" @click="makeCover(task, attachment)"><icon name="image" /> {{ __('Make Cover') }}</div>
                                                        <div v-if="task.cover && task.cover.id === attachment.id" class="cover" @click="removeCover(task)"><icon name="image" /> {{ __('Remove Cover') }}</div>
                                                        <a class="cover" :href="attachment.path" target="_blank"><icon name="link_external" /> {{ __('Open') }}</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </section>

                                <section class="mt-8">
                                    <div>
                                        <div class="flex">
                                            <icon class="w-4 h-4 mr-3 mt-1" name="comments" />
                                            <div class="flex-1 border-b pb-2">
                                                <span class="text-sm font-medium">{{ __('Comments') }}</span>
                                                <span class="ml-2 text-sm font-light ">{{ task.comments.length }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="pl-8 pt-4">
                                        <div>
                                            <div v-if="!showCommentBox" class="mt-1 mb-4 cursor-pointer rounded-md border border-gray-300 hover:shadow">
                                                <p @click="showCommentBox = true" class="px-3 py-2 text-sm ">
                                                    {{ __('Write a comment...') }}
                                                </p>
                                            </div>

                                            <form v-if="showCommentBox" class="mt-1 mb-4 rounded-md border border-gray-300" enctype="multipart/form-data">
<!--                                                <textarea v-model="new_comment.details" class="autosize p-3 comment-textarea block max-h-40 w-full resize-none rounded-md border-0 text-sm focus:ring-0" placeholder="Write a comment..." style="overflow: hidden; overflow-wrap: break-word;background:transparent">{{ new_comment.details || '' }}</textarea>-->
                                                <quill-editor ref="editDescription" @ready="onEditorReady" class="task__description" v-model:content="new_comment.details" :options="editorOptions" contentType="html" theme="snow" />

                                                <div class="flex items-center px-3 pt-2 pb-3">
                                                    <div class="flex items-center">
                                                        <button @click="saveNewComment({details: new_comment.details, task_id: task.id, user_id: $page.props.auth.user.id}, task.comments)" type="button" class="inline-flex items-center rounded border border-gray-300 bg-indigo-600 text-white px-2.5 py-1.5 text-xs font-medium shadow-sm hover:bg-gray-50 hover:text-dark focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                                            {{ __('Save') }}</button>
                                                        <button @click="showCommentBox = false" type="button" class="inline-flex items-center rounded border border-transparent hover:border-gray-300 bg-white px-2.5 py-1.5 text-xs font-medium hover:bg-gray-50 hover:text-gray-900 focus:outline-none focus:ring-0 ltr:ml-1 rtl:mr-1">{{ __('Cancel') }}</button>
                                                    </div>

                                                    <div class="ml-auto hidden flex">
                                                        <label class="cursor-pointer">
                                                            <input accept="image/png, image/jpeg, image/gif,.doc,.docx,.pdf,.txt" class="hidden" type="file" @change="uploadAttachment($event, true)">
                                                            <icon class="w-4 h-4" name="attachment" />
                                                        </label>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>

                                        <div class="space-y-4">
                                            <div v-for="(comment, comment_i) in task.comments" class="group relative flex py-1">
                                                <div class="h-6 w-6">
                                                    <span class="block rounded-full h-6 w-6">
                                                        <img v-if="comment.user.photo_path" class="h-full w-full rounded-full" :src="comment.user.photo_path" alt="">
                                                        <img v-else class="h-full w-full rounded-full" src="/images/user.svg" alt="">
                                                    </span>
                                                </div>

                                                <div class="group flex-1 ltr:pl-4 rtl:pr-4">
                                                    <div class="flex">
                                                        <h2 v-if="comment.user" class="flex text-sm font-medium leading-none">
                                                            {{ comment.user.first_name + ' ' + comment.user.last_name }}
                                                        </h2>
                                                        <span class="text-xs font-normal text-gray-500 ltr:ml-3 rtl:mr-3">{{ moment(comment.created_at).format('MMMM D, YYYY [at] h:mm a') }}</span>
                                                        <div class="ml-auto">
                                                            <div class="absolute right-0 hidden pl-4 group-hover:flex" v-if="$page.props.auth.user.id === comment.user.id">
                                                                <icon class="w-3 h-3 mr-3 cursor-pointer" name="edit" @click="comment.modify = true" />
                                                                <icon class="w-3 h-3 cursor-pointer" name="trash" @click="deleteComment(comment.id, comment_i, task.comments)" />
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="checklist-box2 pt-3 w-full" v-if="comment.modify">
                                                        <quill-editor ref="editComment" @ready="onEditorReady" class="task__description" v-model:content="comment.details" :options="editorOptions" contentType="html" theme="snow" />
                                                        <div class="flex">
                                                            <div class="flex items-center action__buttons mt-2">
                                                                <button type="button" class="small save" @click="saveComment(comment.id, {details: comment.details});comment.modify = false">
                                                                    {{ __('Save') }}</button>
                                                                <button @click="comment.modify = false" type="button" class="small cancel">
                                                                    {{ __('Cancel') }}</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="prose text-sm pt-1" v-if="!comment.modify" v-html="comment.details"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </section>
                            </main>

                            <aside class="w-60 divide-y divide-gray-200 px-6 py-6">
                                <section class="py-3">
                                    <h2 class="px-2 text-sm font-medium">
                                        {{ __('Move Task') }}
                                    </h2>

                                    <div class="relative">
                                        <div>
                                            <div class="group mt-2 flex cursor-pointer items-center td__btn rounded-md px-2 py-1.5 bg-gray-200 dark:bg-white hover:bg-gray-300" @click="displayMoveCard();is_move=true;">
                                                <span class="block h-3.5 text-xs leading-none ">{{ task.list.title }}</span>
                                                <icon class="w-3.5 h-3.5 ml-auto cursor-pointer" name="arrow-down" />
                                            </div>
                                        </div>
                                    </div>
                                </section>
                                <section class="py-3.5">
                                    <div class="flex items-center px-2">
                                        <h2 class="text-sm font-medium ">
                                            {{ __('Assignees') }}
                                        </h2>

                                        <div class="relative ml-auto" modal="true" name="task-assign">
                                            <div>
                                                <span class="cursor-pointer" @click="showAssigneeBox = true"><icon class="h-4 w-4 hover:opacity-80" name="add" /></span>
                                            </div>

                                            <div class="absolute right-1 flex w-[300px] z-10 text-sm flex-col bg-white px-4 py-4 rounded shadow" v-if="showAssigneeBox">
                                                <h4 class="text-center mb-3 font-bold">{{ __('Assignee') }}</h4>
                                                <div class="absolute cursor-pointer hover:bg-gray-200 top-3 right-3 p-1.5 rounded" @click="showAssigneeBox = false" >
                                                    <icon class=" w-4 h-4" name="close" />
                                                </div>
                                                <input id="t_d_s_u" v-model="user_search" class="border-[2px] px-2 py-1 border-gray-400 rounded-[3px]" placeholder="Search User" />
                                                <ul class="flex flex-col mt-3 gap-1 h-48 max-h-48 overflow-y-auto">
                                                    <li v-for="(userObject, user_index) in searchUser(user_search)">
                                                        <label :for="'td_u_id_'+user_index" class="flex p-2 cursor-pointer hover:bg-gray-200 rounded">
                                                            <input :id="'td_u_id_'+user_index" class="w-5 ml-1 mr-2" type="checkbox" :checked="task_assignees().includes(userObject.user_id)" @change="assignUserToTask($event.target.checked, userObject.user_id)">
                                                            <img v-if="userObject.user.photo_path" :aria-label="userObject.user.name" :alt="userObject.user.name" class="w-6 h-6 rounded-full" :src="userObject.user.photo_path" />
                                                            <img v-else :aria-label="userObject.user.name" :alt="userObject.user.name" class="w-6 h-6 rounded-full" src="/images/user.svg" />
                                                            <span data-a="" class="p-1" type="button" :tabindex="user_index">
                                                                {{ userObject.user.name }}
                                                            </span>
                                                        </label>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap gap-1 px-2 mb-1 pt-2">
                                      <span v-for="assignee in task.assignees" :aria-label="assignee.user.name" data-a="" class="block rounded-full h-8 w-8 border-2 border-white">
                                          <img class="h-full w-full rounded-full" :src="assignee.user.photo_path" :alt="assignee.user.name">
                                      </span>
                                    </div>
                                </section>
                                <section class="py-4">
                                    <h2 class="px-2 text-sm font-medium">
                                        {{ __('Time Count') }}
                                    </h2>

                                    <div class="mt-2 flex justify-between items-center px-2">
                                        <div class="flex gap-1 items-center">
                                            <p class="">
                                                {{ totalTime() }}
                                            </p>
                                        </div>
                                        <button v-if="!!this.activeTimerString && task_assignees().includes($page.props.auth.user.id)" class="py-2 w-[70px] bg-red-600 hover:bg-red-700 rounded text-[12px] text-white select-none" @click="stopTracker()">{{ __('STOP') }}</button>
                                        <button v-else-if="!existing_timer && task_assignees().includes($page.props.auth.user.id)" class="py-2 w-[70px] bg-indigo-600 hover:bg-indigo-800 rounded text-[12px] text-white select-none" @click="startTracker()">{{ __('START') }}</button>
                                    </div>
                                </section>
                                <section class="py-3">
                                    <h2 class="px-2 text-sm font-medium">
                                        {{ __('Due Date') }}
                                    </h2>
                                    <div class="relative" modal="true">
                                        <div>
                                            <div class="group mt-2 flex cursor-pointer items-center rounded-md px-2 py-1.5">
                                                <Datepicker v-model="task.due_date" @update:model-value="saveTask({due_date: moment(task.due_date).format('YYYY-MM-DD HH:mm')})" placeholder="Select Date" :is-24="false" />
                                            </div>
                                        </div>
                                    </div>
                                </section>

                                <section class="py-3">
                                    <div class="mt-2 space-y-2 px-1">
                                        <label class="flex cursor-pointer w-full items-center rounded bg-gray-200 td__btn hover:bg-gray-300 px-3 py-2 text-xs font-medium focus:outline-none focus:ring-0">
                                            <input accept="image/png, image/jpeg, image/gif,.doc,.docx,.pdf,.txt" @change="uploadAttachment($event)" class="hidden" type="file"/>
                                            <icon class="mr-2 h-4 w-4 " name="attachment" />
                                            {{ __('Attachment') }}
                                        </label>
                                        <button v-if="!this.task.is_archive" @click="saveTask({ is_archive: 1 });this.task.is_archive = true" class="flex td__btn w-full items-center rounded bg-gray-200 hover:bg-gray-300 px-3 py-2 text-xs font-medium focus:outline-none focus:ring-0">
                                            <icon class="mr-2 h-4 w-4 " name="archive" />
                                            {{ __('Archive') }}
                                        </button>
                                        <button v-else @click="saveTask({ is_archive: 0 });this.task.is_archive = false" class="flex td__btn w-full items-center py-1.5 text-xs font-medium rounded bg-gray-200 hover:bg-gray-300 px-3 py-2">
                                            <icon class="mr-2 h-4 w-4" name="undo" />
                                            {{ __('Revert Back') }}
                                        </button>
                                        <button v-if="this.task.is_archive" @click="deleteTask()" class="flex w-full text-white items-center td__btn py-1.5 text-xs font-medium rounded bg-red-600 hover:bg-red-700 px-3 py-2">
                                            <icon class="mr-2 h-4 w-4 fill-white" name="dash" />
                                            {{ __('Delete') }}
                                        </button>
                                    </div>
                                </section>

                            </aside>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import {Head, Link} from '@inertiajs/vue3'
import Icon from '@/Shared/Icon'
import Loader from '@/Shared/Loader'
import Datepicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css'
import moment from 'moment'
import 'moment-duration-format';
import { QuillEditor } from '@vueup/vue-quill'
import '@vueup/vue-quill/dist/vue-quill.snow.css';
import axios from 'axios'

export default {
    props: {
        id: {
            required: true,
        },
        isPopup: Boolean,
        view: { required: false },
    },
    emits: {closeModal: null},
    data() {
        return {
            showAssigneeBox: false,
            editDescription: false,
            showCommentBox: false,
            showLabelBox: false,
            showMoveCard: false,
            is_move: false,
            label_search: '',
            user_search: '',
            showEditLabelBox: false,
            loading: true,
            newCheckList: false,
            labels: null,
            existing_timer: null,
            users: null,
            list_items: null,
            projects: null,
            counter: { seconds: 0, timer: null, duration: 0 },
            activeTimerString: '',
            new_chek_list: {},
            move_object: {},
            new_comment: {},
            label: {},
            task: {},
            colors: [
                {'name': 'subtle green', 'color': '#baf3db'}, {'name': 'subtle yellow', 'color': '#f8e6a0'}, {'name': 'subtle orange', 'color': '#ffe2bd'}, {'name': 'subtle red', 'color': '#ffd2cc'}, {'name': 'subtle purple', 'color': '#dfd8fd'},
                {'name': 'green', 'color': '#4bce97'}, {'name': 'yellow', 'color': '#e2b203'}, {'name': 'orange', 'color': '#faa53d'}, {'name': 'red', 'color': '#f87462'}, {'name': 'purple', 'color': '#9f8fef'},
                {'name': 'bold green', 'color': '#1f845a'}, {'name': 'bold yellow', 'color': '#946f00'}, {'name': 'bold orange', 'color': '#b65c02'}, {'name': 'bold red', 'color': '#ca3521'}, {'name': 'bold purple', 'color': '#6e5dc6'},
                {'name': 'subtle blue', 'color': '#cce0ff'}, {'name': 'subtle sky', 'color': '#c1f0f5'}, {'name': 'subtle lime', 'color': '#D3F1A7'}, {'name': 'subtle pink', 'color': '#fdd0ec'}, {'name': 'subtle black', 'color': '#dcdfe4'},
                {'name': 'blue', 'color': '#579dff'}, {'name': 'sky', 'color': '#60c6d2'}, {'name': 'lime', 'color': '#94c748'}, {'name': 'pink', 'color': '#e774bb'}, {'name': 'black', 'color': '#8590a2'},
                {'name': 'bold blue', 'color': '#0c66e4'}, {'name': 'bold sky', 'color': '#1d7f8c'}, {'name': 'bold lime', 'color': '#5b7f24'}, {'name': 'bold pink', 'color': '#ae4787'}, {'name': 'bold black', 'color': '#626f86'},
            ],
            editorOptions: {
                modules: {
                    toolbar: {
                        container: [
                            { 'header': [1, 2, 3, 4, 5, 6, false] },
                            'bold', 'italic', 'underline', { 'list': 'bullet' }, { 'list': 'ordered'},
                            'align', 'clean',
                            'blockquote', 'code-block',
                            // 'image'
                        ],
                        // handlers: { 'image': this.imageButtonClickHandler }
                    },
                    // imageDrop: true,
                }
            }
        }
    },
    components: {
        Icon, Loader, Link, Datepicker, QuillEditor, Head
    },
    computed: {

    },
    methods: {
        openNewChecklist(){
            this.newCheckList = true;
            const ref = this.$refs.ncl;
            setTimeout(function(){ ref.focus();},0);
        },
        async imageButtonClickHandler() {
            const input = document.createElement('input');
            input.setAttribute('type', 'file');
            input.click();
            input.onchange = async () => {
                const file = input.files[0];
                this.$refs.editDescription.focus();
            };

        },
        async get_average_rgb(src) {
            return new Promise(resolve => {
                let context = document.createElement("canvas").getContext("2d")
                context.imageSmoothingEnabled = true
                let img = new Image()
                img.src = src
                img.crossOrigin = ""
                img.onload = () => {
                    context.drawImage(img, 0, 0, 1, 1)
                    resolve(context.getImageData(0, 0, 1, 1).data.slice(0, 3))
                }
            })
        },
        async makeCover(task, attachment){
            task.cover = attachment;
            await this.saveTask({cover: attachment.id});
            this.$refs.t__cover.style.backgroundColor = await this.bgColor(task.cover.path)
        },
        removeCover(task){
            this.saveTask({cover: null});
            task.cover = null;
        },
        toggleDetails(){
            this.editDescription = true
        },
        onEditorReady(editor){editor.focus();},
        deleteAttachment(id, index){
            if(this.task.cover && (this.task.cover.id === id)){
                this.task.cover = null;
            }
            axios.post(this.route('task.attachment.delete', id), {}).then((response) => {
                if(response.data){
                    this.task.attachments.splice(index, 1);
                }
            });
        },
        async uploadAttachment(e, is_comment){
            e.preventDefault();
            const file = e.target.files[0];
            const obj = await this.uploadFile(file)
            if(obj && obj.error){
                alert('Please upload image, video, doc/pdf/text file format only!');
            }else{
                this.task.attachments.push(obj)
                if(is_comment){
                    const name = ['jpeg','png','gif','jpg','svg','webp','bmp'].includes(obj.name.split('.').pop())?`<img src="${obj.path}" alt="${obj.name}" />`:`${obj.name}`;
                    const link = `<br/><a href="${obj.path}" target="_blank">${name}</a><br/>`;
                    this.new_comment.details = this.new_comment.details || '' + link;
                }
            }
        },
        async uploadFile(file){
            let formData = new FormData();
            formData.append("file", file);
            const resp = await axios.post(this.route('task.attachment.add', this.task.id), formData,{
                headers: {
                    "Content-Type": "multipart/form-data",
                },
            })
            return resp.data;
        },
        goToLink(link){ window.location.href = link; },
        startTimer(start_now){
            let started = this.counter.timer.started_at ? this.moment.utc(this.counter.timer.started_at) : this.moment();
            let seconds = parseInt(this.moment.duration(this.moment().diff(started)).asSeconds())

            seconds = this.counter.timer.duration + seconds;
            this.counter.ticker = setInterval(() => {
                this.counter.seconds = ++seconds;
                this.activeTimerString = this.moment.duration(this.counter.seconds + parseInt(this.counter.duration), 'seconds').format('h[h] m[m] s[s]')
            }, 1000)
            if(start_now){
                this.eTimer(this.counter)
            }
        },
        eTimer(counter, stopped){
            this.$page.props.counter = counter
            this.$page.props.tracker = {started: true}
            if(stopped){
                this.$page.props.tracker.started = false;
            }
        },
        startTracker(){
            axios.post(this.route('task.timer.start'), {task_id: this.task.id}).then((response) => {
                if(response.data){
                    this.counter.timer = response.data;
                    this.startTimer(true);
                }
            })
        },
        stopTracker(){
            axios.post(this.route('task.timer.stop'), { duration: this.counter.seconds, id: this.counter.timer.id, task_id: this.task.id }).then((response) => {
                if(response.data){
                    this.stopTimer();
                    this.counter.duration = response.data;
                }
            })
        },
        stopTimer(){
            clearInterval(this.counter.ticker)
            this.activeTimerString = ''
            this.eTimer(this.counter, true)
        },
        totalTime(){
            if(this.activeTimerString){
                return this.activeTimerString;
            }else if(this.counter.duration){
                return this.moment.duration(this.counter.duration, 'seconds').format('h[h] m[m] s[s]');
            }
            return '0:00:00'
        },
        calculateTimeSpent(timer){
            if (timer.stopped_at) {
                const started = this.moment(timer.started_at)
                const stopped = this.moment(timer.stopped_at)
                return this.moment.duration(stopped.diff(started)).format();
            }
            return ''
        },
        async moveTask(){
            const project_id = this.move_object.project_id;
            const taskObject = { previous_list: this.task.list_id, new_list: this.move_object.list_id, from: this.task.order, to: this.move_object.order, task_id: this.task.id };
            if(taskObject.previous_list !== taskObject.new_list){
                taskObject.is_move = true;
                await this.saveTask({ list_id: taskObject.new_list })
            }
            if(this.task.project_id !== project_id){
                await this.saveTask({ project_id })
            }
            await this.saveList(project_id, taskObject);
            Object.assign(this.task, { project_id, order: taskObject.to, list_id: taskObject.new_list });
            this.task.project = this.getSelectedProject()
            this.task.list = this.getSelectedList()
            this.showMoveCard = false;
            this.is_move = false;
        },
        saveList(project_id, taskObject){
            axios.post(this.route('task.update.list', project_id),taskObject).catch((error) => {
                console.log(error)
            })
        },
        getSelectedList(){
            let listItem =  this.list_items.filter(l=> {
                return l.id === this.move_object.list_id && l.project_id === this.move_object.project_id
            });
            if(!listItem.length){
                listItem = this.list_items.filter(l=> l.project_id === this.move_object.project_id)
                this.move_object.list_id = listItem[0].id
            }
            return listItem[0];
        },
        getSelectedProjectLists(){
            return this.list_items.filter(l=>l.project_id === this.move_object.project_id);
        },
        getSelectedListPostions(){
            return this.getSelectedList().id === this.task.list_id ? parseInt(this.getSelectedList().tasks_count, 10) : parseInt(this.getSelectedList().tasks_count, 10) + 1;
        },
        getSelectedProject(){
            return this.projects.filter(p=>p.id === this.move_object.project_id)[0];
        },
        displayMoveCard(){
            this.move_object.project_id = this.task.project.id;
            this.move_object.list_id = this.task.list.id;
            this.move_object.order = this.task.order;
            this.showMoveCard = true;
        },
        searchLabel(input){
            return this.labels.filter(lab => lab.name.toLowerCase().indexOf(input) > -1);
        },
        searchUser(input){
            return this.team_members.filter(tm => tm.user.name.toLowerCase().indexOf(input) > -1);
        },
        deleteLabel(id){
            axios.post(this.route('labels.delete', id)).catch((error) => {
                console.log(error)
            })
            const findIndex = this.labels.findIndex(l=>l.id === id);
            this.labels.splice(findIndex, 1);
            const tlIndex = this.task.task_labels.findIndex(tl=>tl.label_id === id);
            if(tlIndex > -1){
                this.task.task_labels.splice(tlIndex, 1);
            }
            this.label = {};
        },
        saveLabel(labelObject){
            axios.post(this.route('labels.save'), labelObject).then((response) => {
                if(response.data && !labelObject.id){
                    this.labels.push(response.data);
                }else if(labelObject.id){
                    const findIndex = this.labels.findIndex(l=>l.id === labelObject.id);
                    const tlIndex = this.task.task_labels.findIndex(tl=>tl.label_id === labelObject.id);
                    this.labels[findIndex] = labelObject;
                    if(tlIndex > -1){
                        this.task.task_labels[tlIndex]['label'] = labelObject;
                    }
                }
                this.showEditLabelBox = false;
                this.showLabelBox = true;
            }).catch((error) => {
                console.log(error)
            })
            this.label = {};
        },
        addLabelToTask(checked, id){
            axios.post(this.route('task.labels.add'), {task_id: this.task.id, label_id: id}).then((response) => {
                if(response.data){
                    if(checked){
                        this.task.task_labels.push(response.data);
                    }else{
                        const findIndex = this.task.task_labels.findIndex(tl=>tl.label_id === id);
                        if(findIndex > -1){
                            this.task.task_labels.splice(findIndex, 1);
                        }
                    }
                }
            }).catch((error) => {
                console.log(error)
            })
        },
        assignUserToTask(checked, id){
            axios.post(this.route('task.assignees.add'), {task_id: this.task.id, user_id: id}).then((response) => {
                if(response.data){
                    if(checked){
                        this.task.assignees.push(response.data);
                        if(response.data.id && response.data.user && response.data.user.id){
                            this.sendNotification('send.mail.task_user_added', response.data.id, response.data.user.id);
                        }
                    }else{
                        const findIndex = this.task.assignees.findIndex(a => a.user_id === id);
                        if(findIndex > -1){
                            this.task.assignees.splice(findIndex, 1);
                        }
                    }
                }
            }).catch((error) => {
                console.log(error)
            })
        },
        task_label_ids(){
            return this.task.task_labels.map(item => item.label_id);
        },
        task_assignees(){
            return this.task.assignees.map(item => item.user_id);
        },
        saveDetails(){
            if(this.task.description){
                const desc = this.task.description;
                this.saveTask({ description: desc });
            }
        },
        async deleteTask(){
            await axios.post(this.route('task.delete', this.task.id), {});
            this.goToLink(this.route(this.view === 'table'?'projects.view.table':'projects.view.board', this.task.project_id));
        },
        saveTask(taskObject){
            axios.post(this.route('task.update', this.task.id), taskObject).then((response) => {
                if(response.data){
                    this.sendNotification('send.mail.task_update', response.data.id)
                }
            })
        },
        checklistDoneCount(checkList){
            return checkList.filter(item => !!item.is_done).length;
        },
        modifyCheck(check_list){
            check_list.modify = true;
            setTimeout(()=> {
                document.getElementById('modify_'+check_list.id).focus()
            }, 10)
        },
        deleteCheckList(id, index, checkLists){
            axios.post(this.route('check_list.delete', id)).catch((error) => {
                console.log(error)
            })
            checkLists.splice(index, 1);
        },
        deleteComment(id, index, comments){
            axios.post(this.route('comment.delete', id)).catch((error) => {
                console.log(error)
            })
            comments.splice(index, 1);
        },
        modifyCheckListSubmit(check_list, c_index, checklist){
            if(!check_list.title){
                this.deleteCheckList(check_list.id, c_index, checklist)
            }else{
                this.saveCheckList(check_list.id, {title: check_list.title});
            }
            check_list.modify = false
        },
        inputNewChecklistAction(check_list, e){
            if((e && e.keyCode === 13) || !e){
                if(!check_list.title){
                    this.newCheckList = false;
                }else{
                    this.saveNewCheckList({title: check_list.title, task_id: this.task.id}, this.task.checklists);
                    this.openNewChecklist()
                }
            }
        },
        async bgColor(path){
            const color = await this.get_average_rgb(path);
            return `rgba(${color[0]},${color[1]},${color[2]})`;
        },
        saveCheckList(id, checkListObject){
            axios.post(this.route('check_list.update', id), checkListObject).catch((error) => {
                console.log(error)
            })
        },
        saveComment(id, commentObject){
            axios.post(this.route('comment.update', id), { details: commentObject.details }).catch((error) => {
                console.log(error)
            })
        },
        saveNewCheckList(checkListObject, currentCheckList){
            this.new_chek_list.title = '';
            axios.post(this.route('check_list.new'), checkListObject).then((response) => {
                if(response.data){
                    currentCheckList.push(response.data);
                }
            }).catch((error) => {
                console.log(error)
            })
        },
        saveNewComment(commentObject, currentComments){
            this.new_comment.details = '';
            commentObject.created_at = this.moment().format('YYYY/MM/DD HH:mm:ss')
            axios.post(this.route('comments.new'), commentObject).then((response) => {
                if(response.data){
                    currentComments.push(response.data);
                    this.showCommentBox = false;
                    this.sendNotification('send.mail.comment', response.data.id)
                }
            }).catch((error) => {
                console.log(error)
            })
        },
        sendNotification(uri, id, user_id){
            const data = {id}
            if(!!user_id){
                data.user_id = user_id;
            }
            axios.post(this.route(uri, data)).catch((error) => {
                console.log(error);
            })
        },
        async getTask(id){
            const taskResponse = await axios.get(this.route('json.task.get', id));
            if(Object.keys(taskResponse.data).length){
                this.task = await taskResponse.data
                this.counter.timer = this.task.timer || null;
                if (this.counter.timer && (this.counter.timer.task_id === this.task.id)){
                    this.startTimer()
                }
                this.loading = false;
                await this.getOtherData();
            }else{
                alert('something went wrong');
            }
        },
        saveTitle(e){
            if (e.keyCode === 13 || e.type === 'blur'){
                e.preventDefault();
                e.target.blur();
                if (e.target.innerText){
                    const title = e.target.innerText;
                    axios.post(this.route('task.update', this.task.id),{ title }).then((response) => {
                        if(response.data){
                            this.sendNotification('send.mail.task_update', response.data.id)
                        }
                    })
                }
            }
        },
        async getOtherData(){
            const dataResponse = await axios.get(this.route('task.other.data', {task_id: this.task.id, project_id: this.task.project_id}));
            const res = dataResponse.data;
            this.labels = res.labels || [];
            this.list_items = res.lists || [];
            this.projects = res.projects || [];
            this.team_members = res.team_members || [];
            this.existing_timer = res.timer || null;
            this.counter.duration = res.duration || 0;
            this.move_object.order = this.task.order;
            if(this.task.cover && this.$refs.t__cover){
                this.$refs.t__cover.style.backgroundColor = await this.bgColor(this.task.cover.path)
            }
        },
    },
    created() {
        this.moment = moment
        this.getTask(this.id)
    },
    mounted() {
        let self = this;
        window.addEventListener('keyup', function(ev) {
            if(ev.key === "Escape"){
                if(self.isPopup){
                    self.$emit('closeModal', true)
                }else{
                    self.goToLink(self.route(self.view === 'table'?'projects.view.table':'projects.view.board', task.project.slug || task.project.id))
                }
            }
        });
    },
    name: "task-details"
};
</script>
