<script setup>
import ChangeDescription from '@/components/utils/ChangeDescription/ChangeDescription.vue';
import ChangeTitle from '@/components/utils/ChangeTitle/ChangeTitle.vue';
import AddSchema from '@/components/utils/Html/AddSchema.vue';
import AddMeta from '@/components/utils/Html/AddMeta.vue';
import Layout from '@/layouts/page/ContactLayout.vue';
import Teams from './parts/Teams.vue';
import ReferencesSlider from './parts/ReferencesSlider.vue';
</script>

<template>
	<Layout title="Team" description="Our team employees.">
		<Teams />
		<ReferencesSlider />
	</Layout>

	<!-- Seo header meta -->
	<!-- <AddSchema :json="item.schema_seo" />
	<AddMeta :json="item.meta_seo" /> -->

	<ChangeTitle :title="'Team'" />
	<ChangeDescription :description="'Our latest team.'" />
</template>

<style>
@import url('@/assets/css/blog.css');
</style>
