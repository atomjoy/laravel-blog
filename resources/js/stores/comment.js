import { ref, computed } from 'vue';
import { defineStore, storeToRefs } from 'pinia';
import router from '@/router';
import axios from 'axios';

export const useCommentStore = defineStore('comment', () => {
	// State
	const error = ref(false);
	const message = ref(null);
	let comment = ref({});
	const is_approved = ref('null');
	let current_page = ref(1);
	let last_page = ref(1);
	let perpage = ref(5);
	let list = ref([]);

	// Getters
	const getComment = computed(() => comment);
	// With value
	const getError = computed(() => error.value);
	const getMessage = computed(() => message.value);

	// Actions
	async function loadList() {
		let res = await axios.get('/web/api/admin/comments?page=' + current_page.value + '&perpage=' + perpage.value);
		list.value = res?.data?.data ?? [];
		current_page.value = res?.data?.paginate.current_page ?? 1;
		last_page.value = res?.data?.paginate.total_pages ?? 1;
		router.push({ query: { page: current_page.value } });
	}

	async function deleteItem(id) {
		try {
			if (confirm('Delete ?')) {
				let res = await axios.delete('/web/api/admin/comments/' + id);
				setMessage(res);
				const row = document.querySelector('.panel_list_row_' + id);
				row.remove();
			}
		} catch (err) {
			setError(err);
		}
	}

	async function updateItem(id, data) {
		try {
			data.append('_method', 'PATCH');
			let res = await axios.post('/web/api/admin/comments/' + id, data);
			setMessage(res);
		} catch (err) {
			setError(err);
		}
		loadItem(id);
		scrollTop();
	}

	async function loadItem(id) {
		try {
			let res = await axios.get('/web/api/admin/comments/' + id);
			comment.value = res?.data?.data;
		} catch (err) {
			setError(err);
		}
	}

	async function removeImage(id) {
		try {
			let res = await axios.get('/web/api/admin/comments/remove/' + id);
			setMessage(res);
			loadItem(id);
		} catch (err) {
			setError(err);
		}
	}

	function setPerpage(value) {
		if (value < 5) {
			perpage.value = 5;
		}
		loadList();
	}

	function prevPage() {
		current_page.value--;
		if (current_page.value <= 0) {
			current_page.value = 1;
		}
		loadList();
	}

	function nextPage() {
		current_page.value++;
		if (current_page.value >= last_page.value) {
			current_page.value = last_page.value;
		}
		loadList();
	}

	function getImagePath(e) {
		comment_image.value = URL.createObjectURL(e.target.files[0]);
		const el = document.querySelector('#img');
		if (el) {
			el.src = comment_image.value;
		}
	}

	function resetForm(e) {
		e.target.reset();
	}

	function clearError() {
		message.value = null;
		error.value = false;
	}

	function setMessage(res) {
		message.value = res?.data?.message;
		error.value = false;
	}

	function setError(err) {
		message.value = err?.response?.data?.message ?? err?.message ?? 'Ups! Invalid data.';
		error.value = true;
	}

	function updateMessage(msg) {
		message.value = msg;
	}

	function updateError(err = true) {
		error.value = err;
	}

	function scrollTop() {
		// Need html tag with overflow-y: scroll
		window.scrollTo({ top: 1, behavior: 'smooth' });
		// document.querySelector(id).scrollIntoView({behavior: 'smooth' });
	}

	return {
		is_approved,
		message,
		error,
		comment,
		current_page,
		last_page,
		perpage,
		list,
		getError,
		getMessage,
		getComment,
		resetForm,
		clearError,
		scrollTop,
		updateMessage,
		updateError,
		getImagePath,
		removeImage,
		loadItem,
		updateItem,
		loadList,
		deleteItem,
		setPerpage,
		prevPage,
		nextPage,
	};
});
