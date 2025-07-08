(function(){
	const {render, useState} = wp.element;
	function App(){
		const [desc,setDesc]=useState('');
		const [loading,setLoading]=useState(false);
		const [msg,setMsg]=useState('');
		const handleSubmit=async(e)=>{
			e.preventDefault();
			if(!desc)return;
			setLoading(true);setMsg('');
			try{
				const sections=[...document.querySelectorAll('input[name="gai_sections[]"]:checked')].map(i=>i.value);
				const res=await wp.apiFetch({path:'/gai/v1/generate',method:'POST',headers:{'X-WP-Nonce':GAI.nonce},data:{description:desc,sections}});
				if(res.success){window.location=res.edit_link;return;}
				setMsg(res.message||'Error');
			}catch(err){setMsg(err.message);}
			setLoading(false);
		};
		return null; // no UI; we only hijack form submit
	}
	document.addEventListener('DOMContentLoaded',()=>{
		const form=document.querySelector('#gai-admin-form');if(form){form.addEventListener('submit',(e)=>e.preventDefault());render(App(),form.querySelector('.gai-react'));}
	});
})();